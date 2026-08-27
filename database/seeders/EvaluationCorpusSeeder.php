<?php

namespace Database\Seeders;

use App\Models\EvaluationCorpusItem;
use App\Models\Framework;
use App\Models\FrameworkControl;
use Illuminate\Database\Seeder;

/**
 * EvaluationCorpusSeeder
 *
 * Populates the 60-item evaluation corpus used by the Chapter 6 and Chapter 7
 * evaluations.
 *
 * The corpus is distributed strictly across four frameworks:
 *   - PCI DSS ............ 24 items
 *   - ISO 27001:2022 ..... 14 items
 *   - BB ICT Guidelines .. 12 items
 *   - HITRUST CSF ........ 10 items
 *
 * Ground-truth verdicts are balanced across the 60 items:
 *   - Compliant .......... 24 items
 *   - Partial ............ 18 items
 *   - Non-Compliant ...... 18 items
 *
 * Each of the five native-parsing evidence types appears exactly 12 times:
 *   screenshot, diagram, policy_page, config_export, log_extract.
 *
 * All mock data uses the sanitized fictional entity "XYZ Bank Ltd." with zero
 * real client data or live credentials.
 *
 * Model-visible separation: synthetic evidence files embed ONLY the
 * evidence_summary text, which carries neutral factual observations about the
 * artefact. ground_truth, truth_rationale and expected_gaps are answer-key
 * fields and are never rendered into evidence presented to the AI model;
 * EvaluationRunService::evidenceBody() enforces this at generation time.
 *
 * The seeder is idempotent. Where the framework_controls table has no row for
 * a referenced control (PCI DSS controls are absent by default), the control
 * is created on the fly so every corpus FK is valid.
 *
 * Run:
 *   php artisan db:seed --class=EvaluationCorpusSeeder
 */
class EvaluationCorpusSeeder extends Seeder
{
    private const EVIDENCE_TYPES = [
        'screenshot',
        'diagram',
        'policy_page',
        'config_export',
        'log_extract',
    ];

    /**
     * PCI DSS v4.0 control definitions used when the control row is missing
     * from framework_controls.
     *
     * @var array<string, string> control_id => requirement_description
     */
    private array $pciDssControls = [
        '1.2.1' => 'Configuration standards are used to configure and maintain firewall and router configurations.',
        '1.3.1' => 'Inbound traffic to the cardholder data environment (CDE) is restricted.',
        '1.3.4' => 'Network traffic is inspected and DMZ and other boundary zones are controlled.',
        '2.2.1' => 'Vendor default accounts, passwords and other default security parameters are removed or changed.',
        '3.3.1' => 'Sensitive Authentication Data (SAD) is not stored after authorization.',
        '3.4.1' => 'Primary Account Numbers (PAN) are rendered unreadable wherever they are stored.',
        '4.2.1' => 'Strong cryptography and security protocols are used to safeguard PAN during transmission over open public networks.',
        '5.2.1' => 'An anti-malware solution is deployed on all system components.',
        '5.4.1' => 'Anti-malware mechanisms are active, running and current.',
        '6.3.1' => 'Security hardening standards are applied to all system components.',
        '6.4.2' => 'Payment-page scripts are managed and monitored for integrity.',
        '7.2.1' => 'Access is established and managed based on least privilege and job responsibilities.',
        '8.2.1' => 'Multi-factor authentication is implemented for all access into the cardholder data environment.',
        '8.3.1' => 'All user access to system components is assigned a unique ID.',
        '8.3.4' => 'Re-authentication is required for users with administrative access.',
        '8.4.2' => 'System authentication parameters are configured to prevent misuse.',
        '8.5.1' => 'Passwords are set at first use and must be changed immediately after.',
        '9.4.2' => 'Physical access to sensitive areas is monitored and recorded.',
        '10.2.1' => 'Audit logs are enabled and active for all system components.',
        '10.4.1' => 'System clocks are synchronized with a single time source.',
        '11.3.1' => 'Internal and external vulnerability scans are performed.',
        '11.6.1' => 'Methods for detecting unauthorized payment devices are implemented.',
        '12.3.1' => 'A targeted risk analysis is performed for each PCI DSS requirement.',
        '12.5.1' => 'An incident response plan is implemented and tested.',
    ];

    /**
     * PCI DSS v4.0 domain label for a control_id prefix.
     */
    private function pciDomain(string $controlId): string
    {
        $prefix = explode('.', $controlId)[0];
        $domains = [
            '1' => 'Requirement 1 - Install and Maintain Network Security Controls',
            '2' => 'Requirement 2 - Secure System and Software',
            '3' => 'Requirement 3 - Protect Stored Account Data',
            '4' => 'Requirement 4 - Protect Cardholder Data with Strong Cryptography',
            '5' => 'Requirement 5 - Protect All Systems and Networks from Malware',
            '6' => 'Requirement 6 - Develop and Maintain Secure Systems and Software',
            '7' => 'Requirement 7 - Restrict Access to System Components and Cardholder Data',
            '8' => 'Requirement 8 - Identify Users and Authenticate Access',
            '9' => 'Requirement 9 - Restrict Physical Access to Cardholder Data',
            '10' => 'Requirement 10 - Log and Monitor All Access',
            '11' => 'Requirement 11 - Test the Security of Systems and Networks',
            '12' => 'Requirement 12 - Support Information Security with Policies and Programs',
        ];

        return $domains[$prefix] ?? 'PCI DSS';
    }

    public function run(): void
    {
        $frameworks = Framework::whereIn('slug', ['pci_dss', 'iso_27001', 'bb_ict', 'hitrust'])
            ->get()
            ->keyBy('slug');

        $missing = ['pci_dss', 'iso_27001', 'bb_ict', 'hitrust'];
        foreach ($frameworks as $slug => $fw) {
            $missing = array_diff($missing, [$slug]);
        }
        if (! empty($missing)) {
            throw new \RuntimeException(
                'Missing required frameworks (slugs): '.implode(', ', $missing)
            );
        }

        $itemCount = 0;

        foreach ($this->corpusItems() as $index => $item) {
            [$frameworkSlug, $controlId, $chapter, $groundTruth, $rationale, $gaps, $evidence] = $item;

            $control = FrameworkControl::firstOrCreate(
                ['framework_id' => $frameworks[$frameworkSlug]->id, 'control_id' => $controlId],
                [
                    'domain' => $this->frameworkDomain($frameworkSlug, $controlId),
                    'requirement_description' => $this->controlDescription($frameworkSlug, $controlId),
                ]
            );

            $evidenceType = self::EVIDENCE_TYPES[$index % count(self::EVIDENCE_TYPES)];
            $evidenceName = $this->evidenceFilename($index + 1, $evidenceType);

            EvaluationCorpusItem::updateOrCreate(
                ['framework_control_id' => $control->id],
                [
                    'chapter' => $chapter,
                    'ground_truth' => $groundTruth,
                    'evidence_type' => $evidenceType,
                    'evidence_name' => $evidenceName,
                    'evidence_summary' => $evidence,
                    'truth_rationale' => $rationale,
                    'expected_gaps' => $gaps,
                ]
            );

            $itemCount++;
        }

        $this->command->info("EvaluationCorpusSeeder complete: {$itemCount} corpus items seeded.");
        $this->command->info('Framework split -> PCI DSS: 24, ISO 27001: 14, BB ICT: 12, HITRUST CSF: 10.');
    }

    private function evidenceFilename(int $position, string $type): string
    {
        $extension = match ($type) {
            'screenshot' => 'png',
            'diagram' => 'png',
            'policy_page' => 'pdf',
            'config_export' => 'txt',
            'log_extract' => 'log',
            default => 'txt',
        };

        return sprintf('XYZ_Bank_Evidence_%03d.%s', $position, $extension);
    }

    /**
     * Framework domain label for a control.
     */
    private function frameworkDomain(string $slug, string $controlId): string
    {
        return match ($slug) {
            'pci_dss' => $this->pciDomain($controlId),
            'iso_27001' => 'ISO 27001:2022',
            'bb_ict' => 'BB ICT Guidelines',
            'hitrust' => 'HITRUST CSF',
            default => 'Compliance',
        };
    }

    /**
     * Requirement description used only when the control row must be created.
     */
    private function controlDescription(string $slug, string $controlId): string
    {
        if ($slug === 'pci_dss') {
            return $this->pciDssControls[$controlId]
                ?? "PCI DSS v4.0 control {$controlId}.";
        }

        return '';
    }

    /**
     * All 60 corpus items in deterministic order. Position-indexed so that
     * evidence types cycle evenly (12 of each type). Each tuple:
     *   [framework-slug, control_id, chapter, ground_truth, rationale, gaps,
     *    evidence]
     * where "evidence" is the model-visible factual observation embedded in
     * the synthetic artefact, and ground_truth/rationale/gaps stay hidden.
     *
     * @return array<int, array{0: string, 1: string, 2: string, 3: string, 4: string, 5: array<int, array<string, string>>, 6: string}>
     */
    private function corpusItems(): array
    {
        return array_merge(
            $this->pciCh6(),
            $this->isoCh6(),
            $this->bbCh6(),
            $this->hitrustCh6(),
            $this->pciCh7(),
            $this->isoCh7(),
            $this->bbCh7(),
            $this->hitrustCh7()
        );
    }

    private function pciCh6(): array
    {
        return [
            ['pci_dss', '1.2.1', 'chapter_6', 'compliant', 'XYZ Bank Ltd. firewall configuration standards match the PCI DSS v4.0 baseline; the firewall rulebase export shows rules aligned with the approved standard and last-review dates within the 6-month window.', [], 'Firewall standards document listing approved baseline settings; rulebase export shows every rule mapped to an approved standard with last-review dates recorded within the past six months.'],
            ['pci_dss', '2.2.1', 'chapter_6', 'compliant', 'Router and switch config exports for XYZ Bank Ltd. show no vendor-default credentials; all default accounts are disabled and changed.', [], 'Router and switch configuration dump showing no vendor-default accounts remain enabled, all default passwords changed at commissioning, and default SNMP community strings replaced.'],
            ['pci_dss', '5.4.1', 'chapter_6', 'compliant', 'Endpoint screenshot and AV console export of XYZ Bank Ltd. show real-time and scheduled scans active, signatures current within 24 hours, and no tampering alerts.', [], 'Anti-malware console export: real-time protection enabled, nightly scheduled scans completed, signature files updated eight hours ago, tamper protection active, zero unresolved alerts.'],
            ['pci_dss', '6.3.1', 'chapter_6', 'compliant', 'The system hardening standard page and CI config export of XYZ Bank Ltd. apply to all CDE components with non-compliant services disabled and patch levels verified.', [], 'System hardening baseline applied across all cardholder-environment hosts; configuration export shows unsupported services disabled and patch levels verified against the hardening standard.'],
            ['pci_dss', '7.2.1', 'chapter_6', 'compliant', 'Access matrix diagram and directory export for XYZ Bank Ltd. map every CDE role to least-privilege entitlements approved by data owners.', [], 'Quarterly access review table listing every CDE role with entitlements restricted to job need; each entry carries data-owner approval and a least-privilege sign-off date.'],
            ['pci_dss', '1.3.4', 'chapter_6', 'partial', 'The network diagram of XYZ Bank Ltd. shows the DMZ isolating web servers with IDS inspection, but it is dated 8 months ago and omits the newly added load balancer.', [['gap' => 'Network diagram out of date; newly added load balancer in the DMZ is not depicted.', 'severity' => 'medium', 'remediation' => 'Re-baseline the DMZ diagram and update it within 30 days of any boundary change.']], 'Network diagram rendering web servers isolated in the DMZ behind IDS inspection; revision stamp dated eight months ago and the recently installed load balancer is missing from the drawing.'],
            ['pci_dss', '3.3.1', 'chapter_6', 'partial', 'Log extracts for XYZ Bank Ltd. show the payment gateway discards track data after authorization, but a retention config export still stores truncated track data in the tokenization sandbox.', [['gap' => 'Residual truncated track data retained in the tokenization sandbox beyond the authorization window.', 'severity' => 'medium', 'remediation' => 'Purge the sandbox SAD store and align retention with PCI DSS guidance.']], 'Data-flow chart showing the payment gateway discards track data once authorization completes; the tokenization sandbox store still holds truncated track data past the allowed retention window.'],
            ['pci_dss', '4.2.1', 'chapter_6', 'partial', 'The TLS config export for XYZ Bank Ltd. internet-facing PAN transmission enforces TLS 1.2+, but the email gateway still delivers payment notifications to a legacy plaintext endpoint.', [['gap' => 'Legacy email relay transmits payment notifications over an unencrypted channel.', 'severity' => 'high', 'remediation' => 'Replace the legacy relay or enforce TLS and remove the plaintext fallback.']], 'Cryptography standard requiring TLS 1.2 or higher on internet-facing PAN transmission; email gateway section shows payment notifications relayed to a legacy endpoint over plaintext SMTP.'],
            ['pci_dss', '6.4.2', 'chapter_6', 'partial', 'The payment-page integrity monitoring screenshot of XYZ Bank Ltd. covers the main checkout page, but script-change alerts are not configured for the new widget subdomain.', [['gap' => 'Integrity monitoring scope misses scripts on the newly added checkout widget subdomain.', 'severity' => 'medium', 'remediation' => 'Add the widget subdomain to the integrity-monitoring and alerting scope.']], 'Integrity monitoring console covering the main checkout page with alerting armed; the checkout widget subdomain introduced last month is absent from the monitored script inventory.'],
            ['pci_dss', '1.3.1', 'chapter_6', 'non_compliant', 'The firewall rulebase export of XYZ Bank Ltd. contains an implicit-deny override rule permitting inbound traffic from the internet to an internal cardholder database on TCP 1433 from any source.', [['gap' => 'Inbound internet access to the internal CDE database is permitted by a broad firewall rule.', 'severity' => 'high', 'remediation' => 'Restrict the rule to business-justified sources and add an explicit deny.']], 'Firewall rulebase export containing an override rule that admits inbound traffic from any internet source to an internal database holding account data on TCP 1433.'],
            ['pci_dss', '3.4.1', 'chapter_6', 'non_compliant', 'A storage scan log extract for XYZ Bank Ltd. found PAN stored as plaintext in the analytics warehouse export files; the config export shows the masking module bypasses that mount point.', [['gap' => 'PAN stored unencrypted/plaintext in analytics warehouse exports.', 'severity' => 'critical', 'remediation' => 'Apply strong one-way hashing or tokenization and remove plaintext PAN from the warehouse.']], 'Storage scan report finding primary account numbers held in cleartext within analytics warehouse export files; masking module configuration confirms that mount point bypasses masking.'],
            ['pci_dss', '5.2.1', 'chapter_6', 'non_compliant', 'The endpoint inventory screenshot for XYZ Bank Ltd. shows the Linux CDE application server has no anti-malware agent installed and no compensating periodic risk evaluation is documented.', [['gap' => 'CDE application server lacks anti-malware protection.', 'severity' => 'high', 'remediation' => 'Deploy an approved anti-malware agent or document the required periodic evaluation.']], 'Endpoint inventory screenshot of the Linux application server in the CDE showing no anti-malware agent installed and no documented periodic risk evaluation for that host.'],
        ];
    }

    private function isoCh6(): array
    {
        return [
            ['iso_27001', '5.1', 'chapter_6', 'compliant', 'The information security policy page of XYZ Bank Ltd. is approved by top management, versioned, and communicated to all staff, satisfying ISO 27001:2022 control 5.1.', [], 'Information security policy page carrying top-management approval signature, version history, and an acknowledgement record showing distribution to all staff.'],
            ['iso_27001', '5.14', 'chapter_6', 'compliant', 'The secure information transfer policy page and file-transfer config export of XYZ Bank Ltd. enforce encryption and the covered channels are logged.', [], 'File-transfer configuration enforcing encryption on all approved transfer channels, with session logging enabled and log retention set to the policy period.'],
            ['iso_27001', '8.2', 'chapter_6', 'compliant', 'The privileged access review log extract of XYZ Bank Ltd. shows quarterly recertification of all privileged accounts with approvals recorded.', [], 'Privileged access review log showing quarterly recertification of every privileged account with reviewer identity and decision recorded for each cycle.'],
            ['iso_27001', '5.19', 'chapter_6', 'partial', 'The supplier risk policy page of XYZ Bank Ltd. mandates risk-based supplier assessment, but onboarding evidence is missing for two legacy cloud service providers.', [['gap' => 'Two legacy cloud providers were not assessed at onboarding as required by supplier security policy.', 'severity' => 'medium', 'remediation' => 'Assess the legacy suppliers and record the accepted risk treatment.']], 'Supplier risk policy mandating risk-based assessment before onboarding; onboarding records show two long-standing cloud service providers were never assessed.'],
            ['iso_27001', '8.9', 'chapter_6', 'partial', 'The CMDB config export of XYZ Bank Ltd. tracks configuration items, but the change history field is blank for the last change window, so several modifications cannot be traced.', [['gap' => 'Configuration change history is incomplete in the CMDB.', 'severity' => 'medium', 'remediation' => 'Enable change-history tracking and re-baseline the CMDB.']], 'CMDB export enumerating configuration items with owners; the change-history column is empty for the latest change window, leaving several modifications untraceable.'],
            ['iso_27001', '5.2', 'chapter_6', 'non_compliant', 'The roles and responsibilities policy page of XYZ Bank Ltd. does not define a named information security officer for the new branch region; no RACI covers that region.', [['gap' => 'No assigned information security role for the new branch region.', 'severity' => 'medium', 'remediation' => 'Assign roles/responsibilities and update the RACI matrix.']], 'Roles and responsibilities page containing no named information security contact for the newly opened branch region and no RACI entries covering it.'],
            ['iso_27001', '5.20', 'chapter_6', 'non_compliant', 'Supplier agreements for XYZ Bank Ltd. reviewed in the policy page export do not include required security clauses for confidentiality and incident notification.', [['gap' => 'Supplier agreements omit mandatory information security clauses.', 'severity' => 'high', 'remediation' => 'Renegotiate or issue amendments adding required clauses to all supplier agreements.']], 'Supplier agreement register excerpt showing confidentiality and incident-notification clauses absent from the reviewed agreements.'],
        ];
    }

    private function bbCh6(): array
    {
        return [
            ['bb_ict', 'Section 2.1', 'chapter_6', 'compliant', 'The BB ICT guidelines coverage map page for XYZ Bank Ltd. documents a formally approved information security policy endorsed by the Board, as required by Section 2.1.', [], 'ICT governance coverage page recording board endorsement of the information security policy with approval date and review cycle.'],
            ['bb_ict', 'Section 1.1', 'chapter_6', 'compliant', 'The governance diagram and steering-committee minutes screenshot of XYZ Bank Ltd. show the ICT governance committee chaired by a director and meeting quarterly.', [], 'Steering-committee minutes screenshot showing the ICT governance session chaired by a director with quarterly attendance records.'],
            ['bb_ict', 'Section 3.2.1', 'chapter_6', 'partial', 'The risk assessment log extract of XYZ Bank Ltd. covers core banking assets, but the schedule shows the last assessment of the digital channel was 14 months ago.', [['gap' => 'Digital channel risk assessment overdue; BB ICT requires a defined periodicity.', 'severity' => 'medium', 'remediation' => 'Execute and document the digital channel risk assessment.']], 'Risk assessment schedule covering core banking assets; the digital channel entry shows its last assessment was completed fourteen months ago.'],
            ['bb_ict', 'Section 4.1.2', 'chapter_6', 'partial', 'The firewall config export of XYZ Bank Ltd. implements the reference firewall architecture, yet the Cisco ASA policy lacks the organization-defined consistency check every 6 months.', [['gap' => 'Firewall rule consistency checks not performed at the required cadence.', 'severity' => 'medium', 'remediation' => 'Schedule and log the 6-month firewall consistency review.']], 'Firewall configuration implementing the reference architecture; the consistency-review field shows no organization-defined six-month check has been logged for the Cisco ASA policy.'],
            ['bb_ict', 'Section 4.1.1', 'chapter_6', 'non_compliant', 'The network diagram for XYZ Bank Ltd. shows cardholder data flowing unencrypted between the branch office and the data center over a non-dedicated link, contrary to Section 4.1.1.', [['gap' => 'Branch-to-DC traffic traverses a shared link without encryption.', 'severity' => 'high', 'remediation' => 'Deploy site-to-site VPN or a dedicated leased line with encryption.']], 'Network diagram tracing cardholder traffic from the branch office to the data center across a shared link with no encryption applied.'],
            ['bb_ict', 'Section 4.1.3', 'chapter_6', 'non_compliant', 'The access-control policy page of XYZ Bank Ltd. does not mandate periodic review of firewall administrator privileges; the privilege list shows dormant accounts still active.', [['gap' => 'Dormant firewall administrator accounts active and no periodic privilege review.', 'severity' => 'high', 'remediation' => 'Disable dormant accounts and implement periodic privilege recertification.']], 'Access-control policy page lacking any periodic review requirement for firewall administrator privileges; privilege listing shows dormant accounts still marked active.'],
        ];
    }

    private function hitrustCh6(): array
    {
        return [
            ['hitrust', '00.a', 'chapter_6', 'compliant', 'The HITRUST CSF certification scope report of XYZ Bank Ltd. documents the full CSF certification scope, authorized by the privacy officer and security officer.', [], 'CSF certification scope report page signed by the privacy officer and the security officer enumerating every system within certification scope.'],
            ['hitrust', '01.a', 'chapter_6', 'compliant', 'The risk management policy page of XYZ Bank Ltd. establishes a risk management process with criteria for risk acceptance, formally approved by management.', [], 'Risk management policy establishing the assessment process with defined acceptance criteria and formal management approval recorded.'],
            ['hitrust', '01.d', 'chapter_6', 'partial', 'The risk register log extract of XYZ Bank Ltd. records identified threats, but 20% of current-year risks lack the required date-stamped reassessment by the privacy officer.', [['gap' => 'Yearly risk reassessment not completed for a subset of register entries.', 'severity' => 'medium', 'remediation' => 'Complete and date-stamp reassessment for all register entries.']], 'Risk register extract recording identified threats per asset; twenty percent of current-year entries carry no date-stamped reassessment by the privacy officer.'],
            ['hitrust', '01.g', 'chapter_6', 'non_compliant', 'The risk register config export of XYZ Bank Ltd. shows the board-level risk decision outcome is not recorded for accepted high risks, violating the HITRUST reporting requirement.', [['gap' => 'Board risk decisions for accepted high risks not documented.', 'severity' => 'medium', 'remediation' => 'Record board review and risk acceptance decisions in the register.']], 'Register export showing accepted high-severity risks with no board-level decision outcome recorded against them.'],
            ['hitrust', '01.h', 'chapter_6', 'non_compliant', 'Internal audit reviews of XYZ Bank Ltd. risk management are absent for the last two cycles; the audit log extract shows no risk-management audit entries.', [['gap' => 'Internal audits of the risk management process not performed.', 'severity' => 'medium', 'remediation' => 'Run and document internal audits of the risk program each cycle.']], 'Internal audit log spanning the last two cycles containing no risk-management audit entries.'],
        ];
    }

    private function pciCh7(): array
    {
        return [
            ['pci_dss', '8.2.1', 'chapter_7', 'compliant', 'The MFA config export of XYZ Bank Ltd. shows multi-factor authentication required for every CDE administrative console and remote access channel, verified through the console screenshot.', [], 'MFA configuration export showing multi-factor authentication required on every administrative console and remote access channel into the CDE.'],
            ['pci_dss', '8.5.1', 'chapter_7', 'compliant', 'The identity provisioning log extract of XYZ Bank Ltd. confirms passwords force-set at first use and changed immediately thereafter for all new user accounts.', [], 'Identity provisioning log confirming force-set first-use passwords flagged for immediate change on every newly created account.'],
            ['pci_dss', '10.4.1', 'chapter_7', 'compliant', 'The NTP config export of XYZ Bank Ltd. points all servers, network devices and storage systems to a single authoritative time source with an offset well under the tolerance.', [], 'NTP configuration export pointing servers, network devices and storage systems to one authoritative time source with observed offset far below tolerance.'],
            ['pci_dss', '12.5.1', 'chapter_7', 'compliant', 'The incident response plan policy page of XYZ Bank Ltd. is complete, approved and was exercised in a tabletop test six months ago, with the debrief attached.', [], 'Incident response plan document, approved and complete, with a tabletop exercise record dated six months ago and debrief notes attached.'],
            ['pci_dss', '8.3.4', 'chapter_7', 'partial', 'The access control config export of XYZ Bank Ltd. enforces re-authentication on administrative sessions, but the claim is not demonstrated for the remote vendor-access portal where sessions persist.', [['gap' => 'Re-authentication not enforced for the remote vendor access portal.', 'severity' => 'medium', 'remediation' => 'Extend session re-authentication to vendor portal sessions.']], 'Access configuration enforcing session re-authentication for administrative sessions; vendor portal section shows persistent sessions with no re-authentication challenge.'],
            ['pci_dss', '9.4.2', 'chapter_7', 'partial', 'The CCTV layout diagram of the XYZ Bank Ltd. data center covers all racks with cardholder data, but the footage retention setting is configured for 45 days rather than the required three months.', [['gap' => 'Physical access monitoring footage retained below the required period.', 'severity' => 'medium', 'remediation' => 'Raise CCTV retention to at least three months.']], 'CCTV layout diagram covering every rack that stores account data; retention setting configured for forty-five days instead of the required three months.'],
            ['pci_dss', '11.3.1', 'chapter_7', 'partial', 'Internal scan exports of XYZ Bank Ltd. were performed quarterly against in-scope hosts, but the external ASV scan window report shows two cardholder-facing IPs scanned only every 100 days.', [['gap' => 'External scan interval exceeded 90 days for two in-scope IPs.', 'severity' => 'medium', 'remediation' => 'Re-scan the two IPs and align the external scan schedule.']], 'Scan window report showing quarterly internal scans across in-scope hosts while two cardholder-facing external addresses were scanned at hundred-day intervals.'],
            ['pci_dss', '12.3.1', 'chapter_7', 'partial', 'The targeted risk analysis document of XYZ Bank Ltd. exists and cites the 12.3.1 elements, but two elements (natural-catastrophe and geographic considerations) are not explicitly addressed.', [['gap' => 'Targeted risk analysis omits specific required elements.', 'severity' => 'medium', 'remediation' => 'Update the risk analysis to address every element listed in 12.3.1.']], 'Targeted risk analysis citing the required elements; natural-catastrophe and geographic considerations are missing from the document.'],
            ['pci_dss', '8.3.1', 'chapter_7', 'non_compliant', 'The user directory export of XYZ Bank Ltd. contains two shared generic accounts used for application service access; related logins are not attributable to a single user.', [['gap' => 'Shared accounts prevent unique identification of users.', 'severity' => 'high', 'remediation' => 'Replace shared accounts with individual credentials and audit entitlements.']], 'User directory export containing two shared generic service accounts whose logins cannot be attributed to a single individual.'],
            ['pci_dss', '8.4.2', 'chapter_7', 'non_compliant', 'The password policy config export of XYZ Bank Ltd. permits passwords unchanged for 180 days and allows reuse of the last three passwords, exceeding the PCI DSS 90-day maximum.', [['gap' => 'Password aging and reuse settings violate PCI DSS 8.4.2 parameters.', 'severity' => 'medium', 'remediation' => 'Enforce a 90-day maximum age and no reuse of the last four passwords.']], 'Password policy export permitting one-hundred-eighty-day password age and reuse of the last three passwords, exceeding permitted parameters.'],
            ['pci_dss', '10.2.1', 'chapter_7', 'non_compliant', 'The security event logging config export of XYZ Bank Ltd. shows audit logs disabled on the database server hosting cardholder data; no log extract exists for that host.', [['gap' => 'Audit logging disabled on the CDE database server.', 'severity' => 'critical', 'remediation' => 'Enable audit logging and verify events for all user/administrator acts.']], 'Logging configuration export showing audit logging disabled on the database server that hosts account data, with no log extract available for that host.'],
            ['pci_dss', '11.6.1', 'chapter_7', 'non_compliant', 'The POS perimeter screenshot of XYZ Bank Ltd. shows no detection mechanism materialized; the deployment status page indicates the unauthorized-device detection tool is not installed at branch sites.', [['gap' => 'No method for detecting unauthorized payment devices implemented.', 'severity' => 'high', 'remediation' => 'Deploy a detection mechanism and inspect branch points of sale periodically.']], 'Point-of-sale perimeter photograph showing no tamper-detection hardware and a deployment status page reporting the unauthorized-device detection tool absent from branch sites.'],
        ];
    }

    private function isoCh7(): array
    {
        return [
            ['iso_27001', '8.20', 'chapter_7', 'compliant', 'The network segmentation diagram of XYZ Bank Ltd. separates the corporate, DMZ and cardholder networks with documented rules; the firewall config export matches the diagram.', [], 'Segmentation diagram separating corporate, DMZ and account-data networks, each inter-zone rule documented and labelled.'],
            ['iso_27001', '8.31', 'chapter_7', 'compliant', 'The environment topology diagram of XYZ Bank Ltd. shows segregated development, test and production environments with separate access control and no promotion of test data.', [], 'Environment topology showing development, test and production networks segregated with independent access control and no test-to-production promotion path.'],
            ['iso_27001', '8.32', 'chapter_7', 'compliant', 'The change management workflow screenshot of XYZ Bank Ltd. shows all production changes recorded, risk-assessed and approved with rollback plans before implementation.', [], 'Change workflow record displaying production changes logged, risk-assessed, approved and carrying rollback plans before implementation.'],
            ['iso_27001', '8.22', 'chapter_7', 'partial', 'The subnet list config export of XYZ Bank Ltd. confirms network segregation, but guest Wi-Fi traffic is observed reaching internal management interfaces in the packet capture excerpt.', [['gap' => 'Guest Wi-Fi traffic reaches internal management interfaces.', 'severity' => 'high', 'remediation' => 'Block guest traffic to management VLAN and re-test segregation.']], 'Subnet listing confirming segregation between user networks; packet capture excerpt shows guest Wi-Fi frames reaching internal management interfaces.'],
            ['iso_27001', '8.24', 'chapter_7', 'partial', 'The TLS/encryption config export of XYZ Bank Ltd. uses approved cipher suites, yet backups destined for the off-site vendor remain encrypted with keys stored in the same backup file.', [['gap' => 'Encryption keys stored alongside the encrypted off-site backups.', 'severity' => 'high', 'remediation' => 'Separate key storage from backup media and rotate the keys.']], 'Backup manifest showing encryption keys stored alongside the encrypted archives they protect.'],
            ['iso_27001', '8.5', 'chapter_7', 'non_compliant', 'The authentication policy page of XYZ Bank Ltd. still permits users to select their own passwords without MFA for the internal staff portal, contrary to control 8.5 requirements.', [['gap' => 'MFA not required for internal staff portal access.', 'severity' => 'high', 'remediation' => 'Enforce MFA for internal staff portal authentication.']], 'Authentication policy permitting self-selected passwords without multi-factor verification for the internal staff portal.'],
            ['iso_27001', '5.37', 'chapter_7', 'non_compliant', 'The operations runbook index of XYZ Bank Ltd. references documented operating procedures, but the standard operating procedure for the branch overnight batch is missing from the index.', [['gap' => 'Mandatory standard operating procedure for the batch job is undocumented.', 'severity' => 'medium', 'remediation' => 'Author, approve and index the batch-run operating procedure.']], 'Operations runbook index referencing documented procedures; the entry for the branch overnight batch points at a procedure document that is not in the repository.'],
        ];
    }

    private function bbCh7(): array
    {
        return [
            ['bb_ict', 'Section 6.2', 'chapter_7', 'compliant', 'The business continuity plan screenshot of XYZ Bank Ltd. matches the BB ICT Section 6.2 requirements, with a documented DR site and quarterly tested recovery objectives.', [], 'Business continuity plan screenshot documenting the disaster recovery site with recovery objectives tested quarterly.'],
            ['bb_ict', 'Section 8.2', 'chapter_7', 'compliant', 'The patch management log extract of XYZ Bank Ltd. confirms security patches on all in-scope systems applied within the BB ICT-defined horizon, verified by the patch install report.', [], 'Patch install report confirming security patches applied across all in-scope systems inside the defined remediation window.'],
            ['bb_ict', 'Section 10.1', 'chapter_7', 'compliant', 'The audit-trail config export of XYZ Bank Ltd. keeps audit logs online for the BB ICT required window and the log integrity checksum report confirms tamper protection.', [], 'Audit-trail configuration retaining logs online for the mandated period, with integrity checksum report confirming tamper protection.'],
            ['bb_ict', 'Section 4.2.1', 'chapter_7', 'partial', 'The router config export of XYZ Bank Ltd. restricts remote management, but the ACL list permits management access from the guest segment for the branch backup router.', [['gap' => 'Remote management access permitted from the guest network segment.', 'severity' => 'high', 'remediation' => 'Restrict management source networks to the management VLAN.']], 'Router configuration restricting remote management; ACL listing admits management sessions from the guest segment to the branch backup router.'],
            ['bb_ict', 'Section 4.1.6', 'chapter_7', 'non_compliant', 'The IDS dashboard screenshot of XYZ Bank Ltd. shows the intrusion detection console offline for the secondary data center; no forwarded alerts or feeds are active.', [['gap' => 'Intrusion detection offline for the secondary data center.', 'severity' => 'high', 'remediation' => 'Restore IDS coverage and verify alert forwarding.']], 'Intrusion detection dashboard showing the secondary data center sensor offline with no forwarded alerts or active feeds.'],
            ['bb_ict', 'Section 4.2.2', 'chapter_7', 'non_compliant', 'The firewall change log extract of XYZ Bank Ltd. shows rules added without the required written firewall change approval, and no fallback plan is attached to those changes.', [['gap' => 'Firewall changes lack written approval and fallback plans.', 'severity' => 'high', 'remediation' => 'Re-attest recent changes and enforce the change-approval workflow.']], 'Firewall change log listing recently inserted rules with no written approval reference and no fallback plan attached.'],
        ];
    }

    private function hitrustCh7(): array
    {
        return [
            ['hitrust', '01.b', 'chapter_7', 'compliant', 'The risk assessment plan screenshot of XYZ Bank Ltd. demonstrates a documented, repeatable risk assessment approach applied consistently across all covered systems.', [], 'Risk assessment plan describing a repeatable assessment method applied uniformly across covered systems.'],
            ['hitrust', '01.c', 'chapter_7', 'compliant', 'The privacy officer approval screenshot of XYZ Bank Ltd. confirms the risk assessment policy is reviewed and approved annually by the privacy officer.', [], 'Policy approval page bearing the privacy officer signature with annual review date recorded for the risk assessment policy.'],
            ['hitrust', '01.e', 'chapter_7', 'partial', 'The risk register log extract of XYZ Bank Ltd. records identified threats and actors for most assets, but the field capturing affected informational assets is empty for two legacy records.', [['gap' => 'Affected asset attribution missing for two legacy risk register records.', 'severity' => 'low', 'remediation' => 'Complete asset attribution and validate all register records.']], 'Risk register extract naming threats and actors for most assets; the affected-informational-assets field is blank on two legacy records.'],
            ['hitrust', '01.f', 'chapter_7', 'partial', 'The risk assessment worksheet of XYZ Bank Ltd. evaluates threats and likelihood, but risk criteria have not been formally adjusted to the new HITRUST control guidance published this year.', [['gap' => 'Risk criteria not refreshed against the latest guidance.', 'severity' => 'medium', 'remediation' => 'Update risk criteria and re-baseline affected assessments.']], 'Assessment worksheet evaluating threats and likelihood; criteria version predates the current published control guidance and has not been adjusted.'],
            ['hitrust', '01.i', 'chapter_7', 'non_compliant', 'The risk escalation policy page of XYZ Bank Ltd. has no defined escalation path to executive management for high-likelihood risks; the escalation flow diagram shows a gap to the executive level.', [['gap' => 'No executive escalation path for high-likelihood risks.', 'severity' => 'medium', 'remediation' => 'Define the executive escalation procedure and update the flow diagram.']], 'Escalation flow diagram showing high-likelihood risks terminating at the operational tier with no route reaching executive management.'],
        ];
    }
}
