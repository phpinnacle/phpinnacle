<required-skills>

- You MUST activate the `engineering-discipline` skill for every task that may modify files in this package.

</required-skills>

# Rosetta Package Instructions

- Keep Rosetta framework-independent. Do not add dependencies on Laravel, Illuminate, Filament, Livewire, or application packages.
- Limit the package to reading, inflating, parsing, and mapping 1C:Enterprise infobase metadata into immutable PHP objects.
- Keep database access behind `Contracts\Connection`; `Connections\PdoConnection` is an adapter and must not own the PDO lifecycle.
- Treat infobase rows and positional serialization as external input. Reject malformed structures explicitly instead of silently repairing or guessing values.
- Preserve the public positional and JSON representation of metadata objects unless a breaking change is explicitly requested.
- Use PHPUnit for package tests. Run the Rosetta tests, its PHPStan configuration at maximum level, and `git diff --check` after implementation changes.
