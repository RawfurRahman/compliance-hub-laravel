const fs = require('fs');
const jsdom = require("jsdom");
const { JSDOM } = jsdom;

const html = fs.readFileSync('output_evidence_7.html', 'utf8');

const dom = new JSDOM(html, { runScripts: "dangerously" });

setTimeout(() => {
    try {
        const workspace = dom.window.premiumEvidenceWorkspace();
        console.log("Workspace initialized successfully:", Object.keys(workspace));
        console.log("Domains:", workspace.domains.length);
        console.log("Requirements:", workspace.requirements.length);
    } catch (e) {
        console.error("Error evaluating workspace:", e);
    }
}, 500);
