#!/usr/bin/env bash
# PostToolUse: formatea con Pint el archivo PHP de back/ que se acaba de editar
f=$(php -r '$d = json_decode(stream_get_contents(STDIN), true); echo str_replace(chr(92), "/", $d["tool_input"]["file_path"] ?? "");')
case "$f" in
  */back/vendor/*) ;;
  */back/*.php) cd "$CLAUDE_PROJECT_DIR/back" && php vendor/bin/pint "$f" ;;
esac
