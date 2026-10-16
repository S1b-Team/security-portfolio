# Vulnerable stage (`FIX=0`)

Container: `lab-app-vulnerable` on host port **8081** (Laravel 12).

`FixMode::enabled()` is false, so FormRequest extras, `SsrfGuard`, webhook token middleware, and ownership policy are skipped. Used only to show attack shapes beside the fixed stack.

Do not expose beyond localhost. Do not load real data.
