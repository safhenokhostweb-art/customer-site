# Authoritative Engineering Rules (Dynamic Loader)

This repository contains mandatory engineering and architectural rules.
Continue MUST follow them before generating or modifying any code.

## Dynamic Rule Discovery

All rule files located in the following patterns are authoritative:

- rules/\*.md
- rules/\*.mdc

Continue must automatically load ALL files matching these patterns.

## Enforcement Policy

1. These rules are mandatory constraints, not documentation.
2. Continue must:
    - Load and check relevant rules before writing code.
    - If multiple rules match a topic, merge and apply all of them.
    - Stop and request clarification when repository evidence is missing.
    - Never invent fields, migrations, services, or architecture.
3. `global-rules.mdc` applies to ALL modules and ALL code.
4. If rules conflict:
    - User clarification is required.
    - Continue must NOT assume.
5. Any output violating rules is invalid for this project.

## Developer Notes

Adding a new rule is simple:

- Create a file inside `/rules/`
- Use .md or .mdc extension
- Continue will automatically include and enforce it
