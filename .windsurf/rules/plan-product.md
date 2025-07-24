---
trigger: manual
---

# Product Planning Workflow

When the user invokes `@plan-product`, follow this workflow:

## 1. Gather Product Information
Ask the user for:
- Product vision and goals
- Target users and use cases
- Key features and functionality
- Technical requirements
- Success metrics

## 2. Reference Global Standards
Check ~/.agent-os/standards/ for:
- Preferred tech stack
- Development practices
- Code style preferences

## 3. Create Product Documentation
Generate these files in .agent-os/product/:

### overview.md
- Product vision and description
- Target audience
- Key value propositions
- Success metrics

### roadmap.md
- Development phases
- Feature priorities
- Timeline estimates
- Dependencies

### architecture.md
- Technical architecture overview
- Technology choices (based on standards)
- System design decisions
- Integration points

### decisions.md
- Key architectural decisions
- Trade-offs considered
- Rationale for choices

## 4. Validation
Review generated documentation with the user and refine as needed.