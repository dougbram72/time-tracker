---
trigger: manual
---

# Feature Specification Workflow

When the user invokes `@create-spec [feature description]`, follow this workflow:

## 1. Reference Context
- Check .agent-os/product/ for product context
- Review ~/.agent-os/standards/ for coding standards
- Check roadmap.md for feature priority

## 2. Create Spec Folder
Create: .agent-os/specs/YYYY-MM-DD-feature-name/

## 3. Generate Specification Documents

### requirements.md
- Feature description and goals
- User stories and acceptance criteria
- Functional requirements
- Non-functional requirements
- Dependencies and constraints

### technical-spec.md
- Implementation approach
- API design (if applicable)
- Database changes
- Architecture decisions
- Testing approach

### tasks.md
- Detailed task breakdown
- Estimated effort per task
- Task dependencies
- Definition of done for each task

## 4. Task Structure
Break down work into:
- **Epic**: Large feature (multiple days)
- **Story**: User-facing functionality (1-2 days)
- **Task**: Development work (2-8 hours)
- **Subtask**: Specific implementation steps (30min-2 hours)

## 5. Validation
Review specification with user before proceeding to implementation.