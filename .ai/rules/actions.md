---
paths:
  - 'app/Actions/**'
---

# Actions

## Actions split into loose mini actions + Orchestrators/ subfolder
Mini actions (one handle(), one responsibility, at most one query/write, never call other minis) live directly in app/Actions/ with no per-domain subfolders. Orchestrators (compose 2+ minis, may open DB::transaction) live in app/Actions/Orchestrators/ with an "Orchestrator" suffix. If a change needs more than one thing to happen, it becomes (or belongs in) an Orchestrator — don't grow a mini into doing two things.
