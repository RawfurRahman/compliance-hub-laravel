const fs = require('fs');

const html = fs.readFileSync('output_evidence_7.html', 'utf8');

const jsonMatch = html.match(/<script id="integrity-hub-data" type="application\/json">\s*(\{[\s\S]*?\})\s*<\/script>/);
if (!jsonMatch) {
    console.error("NO JSON FOUND");
    process.exit(1);
}
const jsonText = jsonMatch[1];
const jsMatch = html.match(/function premiumEvidenceWorkspace\(\) \{([\s\S]*?)return \{/);
if (!jsMatch) {
    console.error("NO JS FOUND");
    process.exit(1);
}

try {
    const _STORE = JSON.parse(jsonText);
    console.log("JSON Parsed OK!");
    console.log("Domains:", Object.values(_STORE.domains).length);
    console.log("Requirements:", Object.values(_STORE.requirements).length);
} catch (e) {
    console.error("JSON Parse Error:", e);
}
