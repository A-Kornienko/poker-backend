# Copilot Instructions

## Core Principles

* Prefer simple, readable, maintainable solutions.
* Follow SOLID pragmatically, not mechanically.
* Avoid overengineering and premature abstraction.
* Keep responsibilities clear and dependencies explicit.
* Prefer composition and dependency injection over inheritance.
* Prefer a small amount of duplication over a premature abstraction.
* Do not introduce services, interfaces, repositories, or layers without a concrete reason.
* Keep business logic out of controllers.
* Reuse existing project conventions and abstractions before introducing new ones.

## Changes & Refactoring

Before creating, modifying, deleting, or refactoring files, stop and ask the user for confirmation.

Before requesting confirmation:

* Explain the proposed implementation approach and request/response flow.
* Describe the sequence of actions and expected behavior.
* List the files that will be created or modified.
* Explain why each change is needed.
* Highlight important assumptions, dependencies, database migrations, or API changes.

Do not make file changes until the user explicitly approves both:

1. The proposed implementation scenario.
2. The planned file changes.

Do not infer approval from discussion, suggestions, or implied intent.

## PHP

* Use PHP 8.3 features where they improve the design, including readonly classes, typed class constants, constructor property promotion, enums, and readonly properties.
* Always use `declare(strict_types=1);`.
* Use strict parameter, return, and property types.
* Avoid `mixed`, `any`-style untyped data, and unnecessary type casts.
* Prefer explicit domain types and value objects where they provide real value.
* Use `interface` only when abstraction or dependency substitution is actually needed.
* Follow PSR standards and existing project conventions.

## Symfony & Architecture

* Use Symfony 6.4-compatible APIs and components.
* Keep controllers thin: validate/map input, delegate application logic, and build the HTTP response.
* Move non-trivial business logic into appropriate services or domain/application classes.
* Do not create a service for trivial orchestration.
* Use constructor dependency injection.
* Prefer autowiring and autoconfiguration; avoid manual service configuration unless necessary.
* Use Symfony Attributes for routing, Doctrine mapping, validation, and other supported metadata instead of legacy annotations.
* Use `#[MapRequestPayload]` for request DTOs when appropriate.
* Use `AbstractController` when its helpers provide a clear benefit; do not require it unnecessarily.
* Use appropriate HTTP methods and status codes according to the API contract.
* Keep API contracts explicit and predictable.

## Doctrine & Database

* Use Doctrine ORM with strictly typed entity properties.
* Use dedicated Doctrine repository classes for non-trivial queries.
* Keep query logic inside repositories rather than controllers or generic services.
* Keep entities focused on domain state and behavior; avoid turning them into data-only containers when domain behavior belongs there.
* Use migrations for every schema change.
* Never modify production schema manually when the change can be represented by a migration.
* Avoid unnecessary eager loading and N+1 queries.
* Fetch only the data required by the use case when performance or payload size matters.

## API & DTOs

* Use DTOs for request data when the input has validation, transformation, or a dedicated API contract.
* Do not expose Doctrine entities directly when the API contract should be independent of persistence.
* Validate external input at the application boundary.
* Return consistent JSON response structures according to the existing API contract.
* Handle validation and domain errors explicitly.
* Do not expose internal exceptions, stack traces, or infrastructure details in API responses.

## Naming & Code Quality

* Use `PascalCase` for classes, `camelCase` for methods and variables, and `snake_case` for database identifiers.
* Use meaningful domain-oriented names.
* Keep methods focused and reasonably small.
* Prefer self-explanatory code over comments.
* Add comments only for non-obvious business rules, constraints, or important implementation decisions.
* Avoid premature performance optimization and unnecessary abstractions.

## Docker & Commands

* Assume PHP/Symfony commands are executed inside the PHP Docker container.
* Use `docker-compose exec php ...` for Symfony Console, Composer, PHPUnit, and related commands.
* Do not assume PHP, Composer, Symfony CLI, or PHPUnit are installed directly on the host.

## Token Efficiency

* Prefer concise, focused implementations.
* Reuse existing services, DTOs, repositories, value objects, and utilities before creating new ones.
* Do not duplicate existing logic.
* Do not generate boilerplate unless it is required.
* When modifying code, change only what is necessary for the requested behavior.
* Avoid repeating unchanged code or context.
* Avoid large architectural refactors when a focused change solves the problem.
* Do not introduce new files, dependencies, layers, interfaces, or abstractions unless they solve a concrete problem.
