#!/usr/bin/env python3
"""PreToolUse hook: blocks Edit/Write/Read on .env-style secret files.

Registered in .claude/settings.json for the "Edit|Write|Read" matcher.
Reads the tool call as JSON on stdin; exits 2 (with a stderr reason) to
block the call, or 0 to let it through.
"""

import json
import os
import sys


def is_blocked_env_file(path: str) -> bool:
    if not path:
        return False

    basename = os.path.basename(path)

    if basename == '.env':
        return True

    # Blocks .env.local, .env.production.local, .env.backup, etc.,
    # but allows the checked-in template .env.example.
    if basename.startswith('.env.') and basename != '.env.example':
        return True

    return False


def main() -> int:
    try:
        payload = json.load(sys.stdin)
    except (json.JSONDecodeError, ValueError):
        # Can't parse the call -- fail open rather than break the tool.
        return 0

    tool_name = payload.get('tool_name', 'This tool')
    tool_input = payload.get('tool_input') or {}
    path = tool_input.get('file_path') or tool_input.get('path') or ''

    if is_blocked_env_file(path):
        print(
            f"Blocked: {tool_name} access to '{path}' is not allowed. "
            "Env files may contain live secrets (API keys, passwords) and are "
            "excluded from direct tool access by .claude/hooks/block-env.py.",
            file=sys.stderr,
        )
        return 2

    return 0


if __name__ == '__main__':
    sys.exit(main())
