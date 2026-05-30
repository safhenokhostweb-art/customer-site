<style>
/* ── Ticket System Design Tokens ── */
:root {
  --ui-indigo:  #4f46e5;
  --ui-success: #10b981;
  --ui-warning: #f59e0b;
}

/* ── Stat Card ── */
.tkt-stat {
  position: relative;
  background: white;
  border-radius: 1rem;
  border: 1px solid #e5e7eb;
  box-shadow: 0 1px 3px rgba(0,0,0,.06);
  padding: 1.25rem;
  overflow: hidden;
  transition: box-shadow .2s, transform .2s;
  cursor: default;
}
.tkt-stat:hover { box-shadow: 0 4px 14px rgba(0,0,0,.09); transform: translateY(-1px); }
.tkt-stat__accent {
  position: absolute; top: 0; right: 0;
  width: 5px; height: 100%;
  border-radius: 0 1rem 1rem 0;
}
.tkt-stat__icon {
  width: 2.75rem; height: 2.75rem;
  border-radius: .75rem;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0; font-size: 1.375rem;
}
.tkt-stat__val  { font-size: 1.875rem; font-weight: 800; line-height: 1; }
.tkt-stat__label { font-size: .75rem; margin-top: .25rem; font-weight: 500; }
.tkt-stat__trend {
  display: inline-flex; align-items: center; gap: .25rem;
  font-size: .625rem; font-weight: 600;
  padding: .15rem .5rem; border-radius: 9999px; margin-top: .375rem;
}

/* ── Ticket Badge ── */
.tkt-badge {
  display: inline-flex; align-items: center; gap: .35rem;
  padding: .25rem .625rem; border-radius: 9999px;
  font-size: .7rem; font-weight: 600; border: 1px solid;
}
.pdot { width: .5rem; height: .5rem; border-radius: 50%; flex-shrink: 0; }

/* ── Glass Cards ── */
.glass-card {
  background: rgba(255,255,255,.85);
  backdrop-filter: blur(8px);
  border: 1px solid rgba(229,231,235,.7);
  border-radius: 1rem;
  box-shadow: 0 1px 4px rgba(0,0,0,.06);
}
.glass-card-strong {
  background: #ffffff;
  border: 1px solid #e5e7eb;
  border-radius: 1rem;
  box-shadow: 0 1px 4px rgba(0,0,0,.06);
}
.glass-filter-bar {
  background: rgba(249,250,251,.9);
  backdrop-filter: blur(8px);
  border: 1px solid #e5e7eb;
  border-radius: 1rem;
  padding: 1rem 1.25rem;
}

/* ── Chat Bubbles ── */
.chat-bg { background: linear-gradient(160deg,#f8faff 0%,#f3f4f6 100%); }
.bubble-customer {
  background: linear-gradient(135deg,#6366f1,#4f46e5);
  color: white;
  border-radius: 1.25rem 1.25rem .375rem 1.25rem;
}
.bubble-customer p { color: white; }
.bubble-staff {
  background: white;
  border: 1px solid #e5e7eb;
  color: #374151;
  border-radius: 1.25rem 1.25rem 1.25rem .375rem;
}
.bubble-internal {
  background: #fffbeb;
  border: 1px solid #fde68a;
  color: #92400e;
  border-radius: 1.25rem;
}

/* ── Buttons ── */
.btn-gradient {
  display: inline-flex; align-items: center; gap: .5rem;
  padding: .625rem 1.25rem; border-radius: .75rem;
  background: linear-gradient(135deg,#4f46e5,#7c3aed);
  color: white; font-size: .875rem; font-weight: 600;
  transition: all .2s;
  box-shadow: 0 2px 8px rgba(79,70,229,.28);
}
.btn-gradient:hover {
  background: linear-gradient(135deg,#4338ca,#6d28d9);
  box-shadow: 0 4px 14px rgba(79,70,229,.38);
  transform: translateY(-1px);
}
.btn-ghost {
  display: inline-flex; align-items: center; gap: .5rem;
  padding: .5rem 1rem; border-radius: .75rem;
  border: 1px solid #e5e7eb; background: white;
  color: #374151; font-size: .875rem; font-weight: 500;
  transition: all .2s;
}
.btn-ghost:hover { background: #f9fafb; }

/* ── Tabs ── */
.tkt-tab {
  display: inline-flex; align-items: center; gap: .375rem;
  padding: .625rem 1rem; font-size: .8125rem; font-weight: 600;
  color: #6b7280;
  border: none; border-bottom: 2px solid transparent;
  background: none; cursor: pointer; white-space: nowrap;
  transition: color .15s, border-color .15s;
}
.tkt-tab:hover { color: #4f46e5; }
.tkt-tab.active { color: #4f46e5; border-bottom-color: #4f46e5; }
.tkt-tab-badge {
  display: inline-flex; align-items: center; justify-content: center;
  min-width: 1.25rem; height: 1.25rem; padding: 0 .3rem;
  border-radius: 9999px;
  background: #6366f1; color: white;
  font-size: .625rem; font-weight: 700;
}

/* ── Queue Health ── */
.queue-health-bar {
  height: .5rem; background: #f3f4f6;
  border-radius: 9999px; overflow: hidden;
}
.queue-health-fill {
  height: 100%; border-radius: 9999px;
  transition: width .5s ease;
}

/* ── Heatmap ── */
.heatmap-cell { border-radius: .5rem; transition: all .2s; min-width: 3rem; }

/* ── Agent Workload ── */
.agent-card {
  padding: .625rem .75rem;
  border-radius: .75rem;
  background: #f9fafb;
  border: 1px solid #f3f4f6;
}
.agent-workload {
  height: .375rem; background: #e5e7eb;
  border-radius: 9999px; overflow: hidden;
}
.agent-workload-bar { height: 100%; border-radius: 9999px; transition: width .5s ease; }

/* ── Timeline ── */
.timeline-item {
  display: flex; align-items: flex-start; gap: .5rem;
  padding: .5rem 0; position: relative;
}
.timeline-item::before {
  content: ''; position: absolute;
  right: .6875rem; top: 1.5rem; bottom: -.5rem;
  width: 1px; background: #e5e7eb;
}
.timeline-item:last-child::before { display: none; }
.timeline-dot {
  width: .875rem; height: .875rem; border-radius: 50%;
  flex-shrink: 0; margin-top: .125rem; position: relative; z-index: 1;
}

/* ── Watcher Chip ── */
.watcher-chip {
  display: inline-flex; align-items: center; gap: .375rem;
  padding: .25rem .625rem;
  background: #f0f4ff; border: 1px solid #c7d2fe;
  border-radius: 9999px; font-size: .7rem; color: #4f46e5;
}

/* ── Feature Pill ── */
.feature-pill {
  background: white; border: 1px solid #e5e7eb;
  border-radius: .875rem; padding: 1rem; text-align: center;
  transition: all .2s;
}
.feature-pill:hover { box-shadow: 0 4px 12px rgba(0,0,0,.08); transform: translateY(-2px); }

/* ── Section Divider ── */
.section-divider {
  display: flex; align-items: center; gap: .75rem;
  margin: 1.5rem 0 .75rem;
  color: #9ca3af; font-size: .75rem; font-weight: 600;
}
.section-divider hr { flex: 1; border: none; border-top: 1px solid #e5e7eb; }

/* ── Escalation Alert ── */
.escalation-alert {
  background: linear-gradient(135deg,#fef2f2,#fee2e2);
  border: 1px solid #fca5a5;
  border-right: 4px solid #ef4444;
  border-radius: 1rem;
}

/* ── Sidebar ── */
.sidebar-section { padding: 1rem 1.25rem; }
.sidebar-title {
  display: flex; align-items: center; gap: .5rem;
  font-size: .8125rem; font-weight: 700; color: #374151;
  margin-bottom: .875rem;
}

/* ── Note Textarea ── */
.note-area { background: #fffbeb !important; border-color: #fde68a !important; }
.note-area:focus { border-color: #f59e0b !important; box-shadow: 0 0 0 3px rgba(245,158,11,.15) !important; }

/* ── Form Input ── */
.tkt-form-input {
  display: block; width: 100%;
  padding: .5rem .75rem;
  border: 1px solid #d1d5db; border-radius: .625rem;
  font-size: .875rem; background: white;
  transition: border-color .15s, box-shadow .15s;
}
.tkt-form-input:focus {
  outline: none;
  border-color: #818cf8;
  box-shadow: 0 0 0 3px rgba(129,140,248,.2);
}
</style>
