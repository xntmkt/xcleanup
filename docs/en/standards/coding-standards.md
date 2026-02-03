# Coding Standards

## General principles
- Follow PSR-12.
- Public functions must have full docblocks.
- Avoid magic numbers; use well-named constants.
- Do not log sensitive data.

## Naming conventions
- Class: PascalCase.
- Method/variable: camelCase.
- Constants: UPPER_SNAKE_CASE.

## Error handling
- Throw semantic exceptions.
- Do not swallow exceptions.

## Review
- Every change must include relevant tests.
