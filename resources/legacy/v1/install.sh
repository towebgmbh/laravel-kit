#!/bin/bash
set -e

# ============================================================
# Laravel Claude Kit — Installer
# Version: 1.0.0
# Scaffolds .claude/ rules, skills, templates, and docs/ into
# an existing Laravel project.
# ============================================================

KIT_VERSION="1.0.0"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
TEMPLATES_DIR="$SCRIPT_DIR/templates"

GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
RED='\033[0;31m'
NC='\033[0m'

# Escape string for use in sed replacement
sed_escape() {
    printf '%s' "$1" | sed 's/[&/\]/\\&/g'
}

echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${BLUE}  Laravel Claude Kit — Installer v${KIT_VERSION}${NC}"
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""

# ── Preflight ──────────────────────────────────────────
if [ ! -f "artisan" ] || [ ! -f "composer.json" ]; then
    echo -e "${RED}❌ This doesn't look like a Laravel project.${NC}"
    echo "   Run this script from the root of your Laravel project."
    exit 1
fi

if [ ! -d "$TEMPLATES_DIR" ]; then
    echo -e "${RED}❌ Templates directory not found at $TEMPLATES_DIR${NC}"
    exit 1
fi

# ── Project Info ───────────────────────────────────────
echo -e "${YELLOW}Project Setup${NC}"
echo ""

read -p "Project name: " PROJECT_NAME
if [ -z "$PROJECT_NAME" ]; then
    PROJECT_NAME=$(basename "$(pwd)")
    echo "  → Using directory name: $PROJECT_NAME"
fi

read -p "Short description: " PROJECT_DESCRIPTION
if [ -z "$PROJECT_DESCRIPTION" ]; then
    PROJECT_DESCRIPTION="A Laravel application"
fi

# ── Frontend Stack ─────────────────────────────────────
echo ""
echo -e "${YELLOW}Frontend Stack${NC}"
echo "  1) Blade + Alpine.js (server-rendered, no SPA)"
echo "  2) Inertia.js + Vue 3 (SPA-like with Laravel backend)"
echo "  3) Livewire (reactive Blade components)"
echo ""
read -p "Choose [1/2/3]: " FRONTEND_CHOICE

case "$FRONTEND_CHOICE" in
    1)
        FRONTEND_VARIANT="blade"
        FRONTEND_TYPE="Laravel + Blade + Alpine.js"
        FRONTEND_TECH="Blade, Alpine.js, Tailwind CSS"
        ;;
    2)
        FRONTEND_VARIANT="inertia-vue"
        FRONTEND_TYPE="Laravel + Inertia.js + Vue 3 SPA"
        FRONTEND_TECH="Vue 3, Inertia.js, Tailwind CSS"
        ;;
    3)
        FRONTEND_VARIANT="livewire"
        FRONTEND_TYPE="Laravel + Livewire"
        FRONTEND_TECH="Livewire 3, Blade, Alpine.js, Tailwind CSS"
        ;;
    *)
        echo "  → Defaulting to Blade"
        FRONTEND_VARIANT="blade"
        FRONTEND_TYPE="Laravel + Blade + Alpine.js"
        FRONTEND_TECH="Blade, Alpine.js, Tailwind CSS"
        ;;
esac

# ── Detect Existing Project ────────────────────────────
HAS_CODE=false
PHP_FILE_COUNT=$(find app -name '*.php' -not -path '*/Providers/*' 2>/dev/null | wc -l | tr -d ' ')
if [ "$PHP_FILE_COUNT" -gt 5 ]; then
    HAS_CODE=true
fi

HAS_EXISTING_DOCS=false
EXISTING_DOCS_COUNT=$(find . -maxdepth 3 -type f \( \
    -iname "PROJECT.md" -o \
    -iname "CONVENTIONS.md" -o \
    -iname "AGENTS.md" -o \
    -iname "STACK.md" -o \
    -iname "ARCHITECTURE.md" -o \
    -iname "WORKFLOW.md" -o \
    -iname "*specification*" -o \
    -iname "*guideline*" \
\) 2>/dev/null | grep -v vendor | grep -v node_modules | wc -l | tr -d ' ')
if [ "$EXISTING_DOCS_COUNT" -gt 0 ]; then
    HAS_EXISTING_DOCS=true
fi

echo ""
echo -e "${YELLOW}Installing...${NC}"

# ── Rules ──────────────────────────────────────────────
mkdir -p .claude/rules

for rule_file in "$TEMPLATES_DIR"/.claude/rules/*.md; do
    [ -f "$rule_file" ] || continue
    filename=$(basename "$rule_file")
    if [ -f ".claude/rules/$filename" ]; then
        echo -e "  ⏭  .claude/rules/$filename (exists)"
    else
        cp "$rule_file" ".claude/rules/$filename"
        echo -e "  ${GREEN}✓${NC} .claude/rules/$filename"
    fi
done

# Frontend variant → 03-frontend.md
VARIANT_FILE="$TEMPLATES_DIR/.claude/rules/variants/03-frontend-${FRONTEND_VARIANT}.md"
if [ -f ".claude/rules/03-frontend.md" ]; then
    echo -e "  ⏭  .claude/rules/03-frontend.md (exists)"
else
    cp "$VARIANT_FILE" ".claude/rules/03-frontend.md"
    echo -e "  ${GREEN}✓${NC} .claude/rules/03-frontend.md (${FRONTEND_VARIANT})"
fi

# ── Skills ─────────────────────────────────────────────
for skill_dir in "$TEMPLATES_DIR"/.claude/skills/*/; do
    [ -d "$skill_dir" ] || continue
    skill_name=$(basename "$skill_dir")
    target_dir=".claude/skills/$skill_name"
    mkdir -p "$target_dir"

    if [ -f "$target_dir/SKILL.md" ]; then
        echo -e "  ⏭  .claude/skills/$skill_name/SKILL.md (exists)"
    else
        cp "$skill_dir/SKILL.md" "$target_dir/SKILL.md"
        echo -e "  ${GREEN}✓${NC} .claude/skills/$skill_name/SKILL.md"
    fi
done

# ── Templates ──────────────────────────────────────────
if [ -d "$TEMPLATES_DIR/.claude/templates" ]; then
    mkdir -p .claude/templates
    for tmpl_file in "$TEMPLATES_DIR"/.claude/templates/*.md; do
        [ -f "$tmpl_file" ] || continue
        filename=$(basename "$tmpl_file")
        if [ -f ".claude/templates/$filename" ]; then
            echo -e "  ⏭  .claude/templates/$filename (exists)"
        else
            cp "$tmpl_file" ".claude/templates/$filename"
            echo -e "  ${GREEN}✓${NC} .claude/templates/$filename"
        fi
    done
fi

# ── Docs ───────────────────────────────────────────────
mkdir -p docs

if [ "$HAS_CODE" = true ]; then
    echo -e "  ${YELLOW}ℹ${NC}  Skipping doc templates (existing project — use /onboard to generate)"
else
    ESCAPED_NAME=$(sed_escape "$PROJECT_NAME")
    ESCAPED_DESC=$(sed_escape "$PROJECT_DESCRIPTION")
    ESCAPED_TYPE=$(sed_escape "$FRONTEND_TYPE")
    ESCAPED_TECH=$(sed_escape "$FRONTEND_TECH")

    for doc_file in "$TEMPLATES_DIR"/docs/*.md; do
        [ -f "$doc_file" ] || continue
        filename=$(basename "$doc_file")
        if [ -f "docs/$filename" ]; then
            echo -e "  ⏭  docs/$filename (exists)"
        else
            sed -e "s/{{PROJECT_NAME}}/$ESCAPED_NAME/g" \
                -e "s/{{PROJECT_DESCRIPTION}}/$ESCAPED_DESC/g" \
                -e "s/{{FRONTEND_TYPE}}/$ESCAPED_TYPE/g" \
                -e "s/{{FRONTEND_TECH}}/$ESCAPED_TECH/g" \
                "$doc_file" > "docs/$filename"
            echo -e "  ${GREEN}✓${NC} docs/$filename"
        fi
    done
fi

# ── Version File ───────────────────────────────────────
echo "$KIT_VERSION" > .claude/.kit-version
echo -e "  ${GREEN}✓${NC} .claude/.kit-version (${KIT_VERSION})"

# ── Summary ────────────────────────────────────────────
echo ""
echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${GREEN}  Done!${NC}"
echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""
echo "Installed:"
echo "  .claude/rules/          → Architecture & convention rules (always active)"
echo "  .claude/rules/03-..     → Frontend: ${FRONTEND_VARIANT}"
echo "  .claude/skills/         → /onboard, /migrate, /review, /test, /brief"
echo "  .claude/templates/      → Feature kickoff template"
if [ "$HAS_CODE" = false ]; then
    echo "  docs/                   → GUIDELINES, SPECIFICATIONS, DECISIONS"
fi
echo ""

if [ "$HAS_CODE" = true ] && [ "$HAS_EXISTING_DOCS" = true ]; then
    echo -e "${YELLOW}Existing project with documentation detected.${NC}"
    echo "  Found ${PHP_FILE_COUNT} PHP files and ${EXISTING_DOCS_COUNT} documentation file(s)."
    echo ""
    echo "  Next steps:"
    echo "    1. Start Claude Code and run: /migrate"
    echo "       This maps your existing docs into the kit's structure."
    echo "       Use '/migrate dry-run' to preview without changes."
    echo "    2. Review the migration plan and confirm"
    echo "    3. Verify with: /brief"
elif [ "$HAS_CODE" = true ]; then
    echo -e "${YELLOW}Existing project detected (${PHP_FILE_COUNT} PHP files).${NC}"
    echo "  The docs/ templates were skipped because /onboard will generate them"
    echo "  from your actual codebase."
    echo ""
    echo "  Next steps:"
    echo "    1. Start Claude Code and run: /onboard"
    echo "    2. Review the generated docs and adjust"
    echo "    3. Verify with: /brief"
else
    echo "  Next steps:"
    echo "    1. Fill in docs/GUIDELINES.md with project specifics"
    echo "    2. Start a session with: /brief"
    echo "    3. Plan your first feature using Claude's Plan mode"
fi
echo ""
echo -e "${YELLOW}Tip:${NC} Rules complement your Boost-generated CLAUDE.md."
echo "     To switch frontend variant, delete .claude/rules/03-frontend.md and re-run."
