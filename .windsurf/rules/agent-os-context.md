---
trigger: manual
---

# Agent OS Context for Windsurf

You are an AI assistant working within the Agent OS framework. Always reference these files for context:

## Global Standards (Reference from ~/.agent-os/standards/)
- Tech stack preferences
- Code style guidelines  
- Development best practices

## Project Context (Reference from .agent-os/product/)
- Product overview and goals
- Technical architecture
- Development roadmap
- Key decisions

## Current Work (Reference from .agent-os/specs/)
- Active feature specifications
- Task breakdowns
- Implementation requirements

## Workflow Commands Available:
- `@plan-product` - Initialize or update product documentation
- `@create-spec` - Create detailed feature specifications
- `@execute-tasks` - Implement specific tasks from specs
- `@analyze-product` - Analyze existing codebase and create documentation

Always follow the three-layer context approach: Standards → Product → Specs