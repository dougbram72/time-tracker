---
trigger: manual
---

# Task Execution Workflow

When the user invokes `@execute-tasks [optional: specific task]`, follow this workflow:

## 1. Context Loading
- Review current spec from .agent-os/specs/[current-spec]/
- Check tasks.md for current status
- Reference technical-spec.md for implementation details
- Apply standards from ~/.agent-os/standards/

## 2. Task Selection
If no specific task mentioned:
- Find next incomplete task from tasks.md
- Prioritize by dependencies and complexity

## 3. Implementation Process
For each task:
1. **Plan**: Break down into concrete steps
2. **Code**: Implement following standards
3. **Test**: Verify functionality
4. **Document**: Update relevant docs
5. **Mark Complete**: Update tasks.md status

## 4. Code Quality Standards
- Follow code-style.md guidelines
- Apply best-practices.md principles
- Use tech-stack.md preferred tools
- Write tests as specified
- Add appropriate documentation

## 5. Progress Tracking
- Update tasks.md with completion status
- Document any blockers or issues
- Update roadmap.md if timeline changes
- Record decisions in decisions.md

## 6. Completion Criteria
Each task is complete when:
- Code is implemented and tested
- Documentation is updated
- Code follows project standards
- Acceptance criteria are met