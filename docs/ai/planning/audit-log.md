# Planning: Audit Log System Implementation

## Phase 1: Foundation (Database & Model)
- [ ] Create Migration for `audit_logs` table.
- [ ] Create `AuditLog` Model.
- [ ] Create `HasAuditLog` Trait.
- [ ] Implement `recordFinancialLog` and `recordAccountDeletionLog` helpers.

## Phase 2: Core Logic (Observers & Hooks)
- [ ] Apply `HasAuditLog` to `Payment` and `CommissionItem`.
- [ ] Apply `HasAuditLog` to `User`, `Student`, `Collaborator`.
- [ ] Implement `PaymentObserver` integration (capture status changes and reverted status).
- [ ] Ensure Soft Delete is active on all tracked models.

## Phase 3: Filament UI
- [ ] Create `AuditLogResource`.
- [ ] Configure List table with Filters.
- [ ] Implement Infolist with Timeline-like structure.
- [ ] Add Export functionality (Excel/PDF).
- [ ] Remove Edit/Delete actions in Filament.

## Phase 4: Access Control & Testing
- [ ] Create `AuditLogPolicy`.
- [ ] Implement Scope in `AuditLogResource` for CTVs.
- [ ] Test recording logs for each event type.
- [ ] Verify IP/Device capture.

## Phase 5: Cleanup & Refactor
- [ ] Evaluate `StudentUpdateLog` (Deprecated/Merge).
- [ ] Optimization of existing logs.
