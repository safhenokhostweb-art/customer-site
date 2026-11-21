# Customer Site

Frontend storefront and tenant-facing web application for the **SaaS Platform** open-source ecosystem.

## Overview

`customer-site` provides the public-facing web interface, tenant onboarding flows, and customer portal connected directly to the central orchestration API. It is designed for rapid multi-tenant customization, clean UI rendering, and seamless integration with automated deployment pipelines.

## Key Features

- **Tenant-Aware Routing & Branding:** Dynamic domain resolution and per-tenant storefront configuration.
- **Self-Service Onboarding:** Automated signup, workspace provisioning triggers, and subscription management.
- **API-First Architecture:** Communicates securely with [`saas-platform`](https://github.com/safhenokhostweb-art/saas-platform) via scoped API tokens and webhooks.

## Ecosystem Repositories

This repository is part of a modular, self-hosted open-source ecosystem:

- **[saas-platform](https://github.com/safhenokhostweb-art/saas-platform)** — Core multi-tenant orchestration engine, RBAC, and central API.
- **[customer-site](https://github.com/safhenokhostweb-art/customer-site)** — Tenant-facing web application and storefront *(this repository)*.
- **[deployment-system](https://github.com/safhenokhostweb-art/deployment-system)** — Automated provisioning, server orchestration, and CI/CD runners.
- **[agents](https://github.com/safhenokhostweb-art/agents)** — AI-driven operational agents for diagnostics, log analysis, and workflow automation.

## License

Released under the [MIT License](LICENSE).
