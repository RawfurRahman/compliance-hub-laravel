<template>
  <DashboardShell
    :filters="filters"
    :refreshing="isRefreshing"
    @update:filters="onFilterChange"
    @reset-filters="resetFilters"
    @refresh="refreshAll"
  >
    <template #kpis>
      <KpiCard label="Active Projects" :value="kpi.data?.projects" color="blue" :loading="kpi.loading.value" :error="kpi.error.value">
        <template #icon><svg class="w-4 h-4 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg></template>
      </KpiCard>
      <KpiCard label="Frameworks" :value="kpi.data?.frameworks" color="purple" :loading="kpi.loading.value" :error="kpi.error.value">
        <template #icon><svg class="w-4 h-4 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg></template>
      </KpiCard>
      <KpiCard label="Finding Compliance" :value="kpi.data?.compliance_pct" subtitle="of findings compliant" color="emerald" :loading="kpi.loading.value" :error="kpi.error.value">
        <template #icon><svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg></template>
      </KpiCard>
      <KpiCard label="Overdue" :value="kpi.data?.overdue_findings" subtitle="findings past due" color="red" :trend-down="(kpi.data?.overdue_findings ?? 0) > 0" :loading="kpi.loading.value" :error="kpi.error.value">
        <template #icon><svg class="w-4 h-4 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></template>
      </KpiCard>
    </template>

    <template #heatmap>
      <RiskHeatmapGrid :cells="heatmap.data ?? []" :loading="heatmap.loading.value" :error="heatmap.error.value" />
    </template>

    <template #inherent-residual>
      <InherentResidualBarChart :items="inherentResidual.data ?? []" :loading="inherentResidual.loading.value" :error="inherentResidual.error.value" />
    </template>

    <template #control-effectiveness>
      <ControlEffectivenessDonut
        :effective="controlEffectiveness.data?.effective ?? 0"
        :partial="controlEffectiveness.data?.partial ?? 0"
        :ineffective="controlEffectiveness.data?.ineffective ?? 0"
        :loading="controlEffectiveness.loading.value"
        :error="controlEffectiveness.error.value"
      />
    </template>

    <template #audit-findings>
      <AuditFindingsDonut :data="auditFindings.data?.severity_breakdown ?? {}" :loading="auditFindings.loading.value" :error="auditFindings.error.value" />
    </template>

    <template #third-party-risk>
      <ThirdPartyRiskDonut
        :breakdown="thirdPartyRisk.data?.risk_tier_breakdown ?? {}"
        :total-vendors="thirdPartyRisk.data?.total_vendors ?? 0"
        :loading="thirdPartyRisk.loading.value"
        :error="thirdPartyRisk.error.value"
      />
    </template>

    <template #policy-governance>
      <PolicyGovernanceCards :data="policyGovernance.data ?? {}" :loading="policyGovernance.loading.value" :error="policyGovernance.error.value" />
    </template>

    <template #top-risks>
      <TopRisksTable :rankings="riskRankings.data ?? []" :loading="riskRankings.loading.value" :error="riskRankings.error.value" />
    </template>

    <template #issue-aging>
      <IssueAgingBarChart :buckets="issueAging.data?.buckets ?? {}" :loading="issueAging.loading.value" :error="issueAging.error.value" />
    </template>

    <template #remediation-trend>
      <RemediationTrendChart :monthly="remediationTrend.data?.monthly_trend ?? []" :loading="remediationTrend.loading.value" :error="remediationTrend.error.value" />
    </template>

    <template #compliance-scorecard>
      <ComplianceScorecardTable :scorecard="complianceScorecard.data ?? []" :loading="complianceScorecard.loading.value" :error="complianceScorecard.error.value" />
    </template>

    <template #ownership>
      <OwnershipAccountabilityTable :ownership="ownership.data?.ownership ?? {}" :sla="ownership.data?.sla ?? {}" :loading="ownership.loading.value" :error="ownership.error.value" />
    </template>
  </DashboardShell>
</template>

<script setup>
import { computed } from 'vue'
import DashboardShell from './components/DashboardShell.vue'
import KpiCard from './components/KpiCard.vue'
import RiskHeatmapGrid from './components/RiskHeatmapGrid.vue'
import TopRisksTable from './components/TopRisksTable.vue'
import InherentResidualBarChart from './components/InherentResidualBarChart.vue'
import ControlEffectivenessDonut from './components/ControlEffectivenessDonut.vue'
import AuditFindingsDonut from './components/AuditFindingsDonut.vue'
import RemediationTrendChart from './components/RemediationTrendChart.vue'
import IssueAgingBarChart from './components/IssueAgingBarChart.vue'
import ThirdPartyRiskDonut from './components/ThirdPartyRiskDonut.vue'
import PolicyGovernanceCards from './components/PolicyGovernanceCards.vue'
import ComplianceScorecardTable from './components/ComplianceScorecardTable.vue'
import OwnershipAccountabilityTable from './components/OwnershipAccountabilityTable.vue'
import { useDashboardFilters } from './composables/useDashboardFilters.js'
import { useDashboardApi } from './composables/useDashboardApi.js'

const { filters, toQueryParams, reset } = useDashboardFilters()

function api(endpoint) {
  return useDashboardApi(endpoint, toQueryParams)
}

const kpi = api('kpis')
const heatmap = api('heatmap')
const riskRankings = api('top-risks')
const inherentResidual = api('inherent-vs-residual')
const controlEffectiveness = api('control-effectiveness')
const complianceScorecard = api('compliance-scorecard')
const auditFindings = api('audit-findings')
const remediationTrend = api('issues-remediation-trends')
const issueAging = api('issue-aging')
const thirdPartyRisk = api('third-party-risk')
const policyGovernance = api('policy-governance')
const complianceTests = api('compliance-tests')
const ownership = api('ownership-matrix')

const isRefreshing = computed(() =>
  [kpi, heatmap, riskRankings, inherentResidual, controlEffectiveness,
   complianceScorecard, auditFindings, remediationTrend, issueAging,
   thirdPartyRisk, policyGovernance, ownership].some(a => a.loading.value)
)

function onFilterChange(newFilters) {
  Object.assign(filters, newFilters)
}

function resetFilters() {
  reset()
}

function refreshAll() {
  ;[kpi, heatmap, riskRankings, inherentResidual, controlEffectiveness,
    complianceScorecard, auditFindings, remediationTrend, issueAging,
    thirdPartyRisk, policyGovernance, ownership].forEach(a => a.refresh())
}
</script>
