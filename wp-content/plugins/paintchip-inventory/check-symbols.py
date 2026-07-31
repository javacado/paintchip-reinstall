#!/usr/bin/env python3
"""
Catches what `php -l` cannot: a call to a method that does not exist.

Syntax checking passes happily on `PCI_Sourcing::sourceable_vends()` when that
method was never defined — the failure only appears at runtime, as a blank
admin page. This walks every PCI_* class, collects the methods it defines and
the static calls it makes, and reports any call with no matching definition.
"""
import re, sys, pathlib

root = pathlib.Path(sys.argv[1] if len(sys.argv) > 1 else '.')
files = sorted(root.rglob('*.php'))

defined = {}   # class -> set(methods)
consts  = {}   # class -> set(constants)
calls   = []   # (file, line, class, method)

class_re  = re.compile(r'^\s*(?:abstract\s+|final\s+)?(?:class|interface)\s+(PCI_\w+)')
method_re = re.compile(r'^\s*(?:public|private|protected)?\s*(?:static\s+)?function\s+(\w+)\s*\(')
const_re  = re.compile(r'^\s*const\s+(\w+)')
call_re   = re.compile(r'\b(PCI_\w+)::(\w+)\s*\(')
cref_re   = re.compile(r'\b(PCI_\w+)::([A-Z][A-Z0-9_]*)\b(?!\s*\()')

for f in files:
    cur = None
    for n, line in enumerate(f.read_text().split('\n'), 1):
        m = class_re.match(line)
        if m:
            cur = m.group(1)
            defined.setdefault(cur, set())
            consts.setdefault(cur, set())
            continue
        if cur:
            m = method_re.match(line)
            if m:
                defined[cur].add(m.group(1))
            m = const_re.match(line)
            if m:
                consts[cur].add(m.group(1))

for f in files:
    for n, line in enumerate(f.read_text().split('\n'), 1):
        if line.lstrip().startswith(('*', '//', '#')):
            continue
        for cls, meth in call_re.findall(line):
            calls.append((f, n, cls, meth, 'method'))
        for cls, const in cref_re.findall(line):
            calls.append((f, n, cls, const, 'const'))

problems = []
for f, n, cls, name, kind in calls:
    if cls not in defined:
        problems.append(f"{f.name}:{n}  unknown class {cls}")
        continue
    pool = defined[cls] if kind == 'method' else consts[cls]
    if name not in pool:
        problems.append(f"{f.name}:{n}  {cls}::{name} — no such {kind}")

print(f"classes: {len(defined)}   static references checked: {len(calls)}")
for c in sorted(defined):
    print(f"  {c:26s} {len(defined[c]):2d} methods, {len(consts[c]):2d} constants")

if problems:
    print(f"\nPROBLEMS ({len(problems)}):")
    for p in sorted(set(problems)):
        print("  " + p)
    sys.exit(1)

print("\nAll static references resolve.")
