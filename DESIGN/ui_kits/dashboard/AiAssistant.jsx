(() => {
const { Card, AssistantOrb, AssistantComposer, SuggestionCard, Button } = window.UpscaleHubDesignSystem_b4eac1;

function AiAssistant() {
  const [sent, setSent] = React.useState([]);
  const [draft, setDraft] = React.useState("");
  const send = () => {
    const text = draft.trim();
    if (!text) return;
    setSent((s) => [...s, { q: text, a: "Here is what I found across your accounts — spending is 6% below your monthly average, and your projected balance at month end is $22,410." }]);
    setDraft("");
  };
  return (
    <Card style={{ minHeight: 560, padding: "var(--card-padding-lg)" }}>
      {sent.length === 0 ? (
        <div style={{ display: "grid", justifyItems: "center", gap: 10, textAlign: "center", padding: "36px 0 22px" }}>
          <AssistantOrb size={84} />
          <span style={{ font: "var(--type-body-sm)", color: "var(--text-muted)" }}>Good to see you!</span>
          <h2 style={{ font: "var(--weight-semibold) var(--text-xl)/1.2 var(--font-ui)", color: "var(--ink-900)" }}>How Can I Assist You With Your Finances?</h2>
          <p style={{ margin: 0, maxWidth: 420, font: "var(--type-body-sm)", color: "var(--text-muted)" }}>
            Quickly track cash flow, get AI-powered insights, and manage your money&mdash;all in one place
          </p>
        </div>
      ) : (
        <div style={{ display: "grid", gap: 14, padding: "10px 0 22px", maxWidth: 680, margin: "0 auto", width: "100%" }}>
          {sent.map((m, i) => (
            <React.Fragment key={i}>
              <div style={{ justifySelf: "end", maxWidth: "80%", padding: "10px 14px", background: "var(--surface-brand)", borderRadius: "var(--radius-lg) var(--radius-lg) var(--radius-xs) var(--radius-lg)", font: "var(--type-body-sm)", color: "var(--white)" }}>{m.q}</div>
              <div style={{ display: "flex", gap: 10, maxWidth: "88%" }}>
                <AssistantOrb size={26} style={{ marginTop: 2 }} />
                <div style={{ padding: "10px 14px", background: "var(--surface-sunken)", border: "1px solid var(--border-subtle)", borderRadius: "var(--radius-lg) var(--radius-lg) var(--radius-lg) var(--radius-xs)", font: "var(--type-body-sm)", color: "var(--ink-900)" }}>{m.a}</div>
              </div>
            </React.Fragment>
          ))}
        </div>
      )}

      <div style={{ maxWidth: 680, width: "100%", margin: "0 auto" }}>
        <AssistantComposer value={draft} onChange={(e) => setDraft(e.target.value)} onSend={send} />
      </div>

      {sent.length === 0 ? (
        <div style={{ maxWidth: 680, width: "100%", margin: "22px auto 0" }}>
          <span style={{ display: "block", marginBottom: 10, font: "var(--type-label)", color: "var(--text-muted)" }}>Get started with an example below</span>
          <div style={{ display: "grid", gridTemplateColumns: "1fr 1fr", gap: 10 }}>
            {window.FX.aiPrompts.map((p) => (
              <SuggestionCard key={p.title} {...p} onClick={() => { setDraft(p.title); }} />
            ))}
          </div>
        </div>
      ) : null}
    </Card>
  );
}

Object.assign(window, { AiAssistant });

})();
