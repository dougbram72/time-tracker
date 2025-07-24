---
trigger: manual
---

# Product Analysis Workflow

When the user invokes `@analyze-product`, follow this workflow for existing codebases:

## 1. Codebase Analysis
Examine the existing code to understand:
- Current architecture and patterns
- Technology stack in use
- Code organization and structure
- Existing documentation
- Test coverage and quality

## 2. Generate Agent OS Documentation
Create missing Agent OS structure:

### Product Documentation (.agent-os/product/)
- **overview.md**: Infer product purpose and goals from codebase
- **architecture.md**: Document current technical architecture
- **roadmap.md**: Identify potential improvements and features
- **decisions.md**: Document apparent architectural decisions

### Standards Alignment
Compare current codebase with ~/.agent-os/standards/:
- Identify gaps in coding standards
- Document current patterns vs. preferred patterns
- Suggest improvements for consistency

## 3. Integration Planning
- Identify areas where Agent OS can immediately help
- Suggest first features to implement with new workflow
- Plan gradual adoption strategy

## 4. Recommendations
Provide specific recommendations for:
- Immediate improvements
- Long-term architectural changes
- Development process enhancements
- Team adoption strategies