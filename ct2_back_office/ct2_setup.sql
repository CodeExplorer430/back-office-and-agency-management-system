CREATE DATABASE IF NOT EXISTS ct2_back_office CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ct2_back_office;

CREATE TABLE IF NOT EXISTS ct2_users (
    ct2_user_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    display_name VARCHAR(190) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    last_login_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ct2_roles (
    ct2_role_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role_key VARCHAR(80) NOT NULL UNIQUE,
    role_name VARCHAR(120) NOT NULL,
    description VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ct2_user_roles (
    ct2_user_role_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ct2_user_id INT UNSIGNED NOT NULL,
    ct2_role_id INT UNSIGNED NOT NULL,
    assigned_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_ct2_user_role (ct2_user_id, ct2_role_id),
    CONSTRAINT fk_ct2_user_roles_user
        FOREIGN KEY (ct2_user_id) REFERENCES ct2_users (ct2_user_id)
        ON DELETE CASCADE,
    CONSTRAINT fk_ct2_user_roles_role
        FOREIGN KEY (ct2_role_id) REFERENCES ct2_roles (ct2_role_id)
        ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ct2_role_permissions (
    ct2_role_permission_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ct2_role_id INT UNSIGNED NOT NULL,
    permission_key VARCHAR(120) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_ct2_role_permission (ct2_role_id, permission_key),
    CONSTRAINT fk_ct2_role_permissions_role
        FOREIGN KEY (ct2_role_id) REFERENCES ct2_roles (ct2_role_id)
        ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ct2_session_logs (
    ct2_session_log_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ct2_user_id INT UNSIGNED NOT NULL,
    session_identifier VARCHAR(128) NOT NULL,
    login_at DATETIME NOT NULL,
    logout_at DATETIME NULL,
    ip_address VARCHAR(64) NOT NULL DEFAULT '127.0.0.1',
    user_agent VARCHAR(255) NOT NULL DEFAULT '',
    CONSTRAINT fk_ct2_session_logs_user
        FOREIGN KEY (ct2_user_id) REFERENCES ct2_users (ct2_user_id)
        ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ct2_audit_logs (
    ct2_audit_log_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ct2_user_id INT UNSIGNED NULL,
    entity_type VARCHAR(120) NOT NULL,
    entity_id INT UNSIGNED NULL,
    action_key VARCHAR(120) NOT NULL,
    details_json JSON NULL,
    ip_address VARCHAR(64) NOT NULL DEFAULT '127.0.0.1',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ct2_audit_entity (entity_type, entity_id),
    CONSTRAINT fk_ct2_audit_logs_user
        FOREIGN KEY (ct2_user_id) REFERENCES ct2_users (ct2_user_id)
        ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ct2_api_logs (
    ct2_api_log_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ct2_user_id INT UNSIGNED NULL,
    endpoint_name VARCHAR(120) NOT NULL,
    http_method VARCHAR(10) NOT NULL,
    status_code SMALLINT UNSIGNED NOT NULL,
    request_summary TEXT NULL,
    response_summary TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_ct2_api_logs_user
        FOREIGN KEY (ct2_user_id) REFERENCES ct2_users (ct2_user_id)
        ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ct2_notifications (
    ct2_notification_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ct2_user_id INT UNSIGNED NOT NULL,
    notification_type VARCHAR(80) NOT NULL,
    notification_title VARCHAR(190) NOT NULL,
    notification_body TEXT NOT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_ct2_notifications_user
        FOREIGN KEY (ct2_user_id) REFERENCES ct2_users (ct2_user_id)
        ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ct2_documents (
    ct2_document_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    entity_type VARCHAR(120) NOT NULL,
    entity_id INT UNSIGNED NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    mime_type VARCHAR(120) NOT NULL,
    uploaded_by INT UNSIGNED NULL,
    uploaded_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ct2_documents_entity (entity_type, entity_id),
    CONSTRAINT fk_ct2_documents_user
        FOREIGN KEY (uploaded_by) REFERENCES ct2_users (ct2_user_id)
        ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ct2_external_refs (
    ct2_external_ref_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    entity_type VARCHAR(120) NOT NULL,
    entity_id INT UNSIGNED NOT NULL,
    source_system VARCHAR(80) NOT NULL,
    external_identifier VARCHAR(190) NOT NULL,
    metadata_json JSON NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_ct2_external_ref (entity_type, entity_id, source_system, external_identifier)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ct2_staff (
    ct2_staff_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ct2_user_id INT UNSIGNED NULL,
    staff_code VARCHAR(60) NOT NULL UNIQUE,
    full_name VARCHAR(190) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    phone VARCHAR(60) NOT NULL,
    department VARCHAR(120) NOT NULL,
    position_title VARCHAR(120) NOT NULL,
    employment_status ENUM('active', 'inactive', 'suspended') NOT NULL DEFAULT 'active',
    availability_status ENUM('available', 'busy', 'on_leave') NOT NULL DEFAULT 'available',
    team_name VARCHAR(120) NOT NULL,
    notes TEXT NULL,
    created_by INT UNSIGNED NULL,
    updated_by INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_ct2_staff_user
        FOREIGN KEY (ct2_user_id) REFERENCES ct2_users (ct2_user_id)
        ON DELETE SET NULL,
    CONSTRAINT fk_ct2_staff_created_by
        FOREIGN KEY (created_by) REFERENCES ct2_users (ct2_user_id)
        ON DELETE SET NULL,
    CONSTRAINT fk_ct2_staff_updated_by
        FOREIGN KEY (updated_by) REFERENCES ct2_users (ct2_user_id)
        ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ct2_agents (
    ct2_agent_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    agent_code VARCHAR(60) NOT NULL UNIQUE,
    agency_name VARCHAR(190) NOT NULL,
    contact_person VARCHAR(190) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    phone VARCHAR(60) NOT NULL,
    region VARCHAR(120) NOT NULL,
    commission_rate DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    support_level ENUM('standard', 'priority', 'strategic') NOT NULL DEFAULT 'standard',
    approval_status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    active_status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    external_booking_id VARCHAR(120) NULL,
    external_customer_id VARCHAR(120) NULL,
    external_payment_id VARCHAR(120) NULL,
    source_system VARCHAR(80) NULL,
    created_by INT UNSIGNED NULL,
    updated_by INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_ct2_agents_created_by
        FOREIGN KEY (created_by) REFERENCES ct2_users (ct2_user_id)
        ON DELETE SET NULL,
    CONSTRAINT fk_ct2_agents_updated_by
        FOREIGN KEY (updated_by) REFERENCES ct2_users (ct2_user_id)
        ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ct2_agent_staff_assignments (
    ct2_assignment_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ct2_agent_id INT UNSIGNED NOT NULL,
    ct2_staff_id INT UNSIGNED NOT NULL,
    assignment_role VARCHAR(120) NOT NULL,
    assignment_status ENUM('active', 'paused', 'completed') NOT NULL DEFAULT 'active',
    start_date DATE NOT NULL,
    end_date DATE NULL,
    notes TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_ct2_agent_staff_assignment (ct2_agent_id, ct2_staff_id, assignment_role),
    CONSTRAINT fk_ct2_assignments_agent
        FOREIGN KEY (ct2_agent_id) REFERENCES ct2_agents (ct2_agent_id)
        ON DELETE CASCADE,
    CONSTRAINT fk_ct2_assignments_staff
        FOREIGN KEY (ct2_staff_id) REFERENCES ct2_staff (ct2_staff_id)
        ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ct2_approval_workflows (
    ct2_approval_workflow_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    subject_type VARCHAR(120) NOT NULL,
    subject_id INT UNSIGNED NOT NULL,
    requested_by INT UNSIGNED NULL,
    approver_user_id INT UNSIGNED NULL,
    approval_status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    requested_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    decided_at DATETIME NULL,
    decision_notes TEXT NULL,
    INDEX idx_ct2_approvals_subject (subject_type, subject_id),
    CONSTRAINT fk_ct2_approvals_requested_by
        FOREIGN KEY (requested_by) REFERENCES ct2_users (ct2_user_id)
        ON DELETE SET NULL,
    CONSTRAINT fk_ct2_approvals_approver_user
        FOREIGN KEY (approver_user_id) REFERENCES ct2_users (ct2_user_id)
        ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ct2_activity_logs (
    ct2_activity_log_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    subject_type VARCHAR(120) NOT NULL,
    subject_id INT UNSIGNED NOT NULL,
    activity_type VARCHAR(120) NOT NULL,
    activity_summary VARCHAR(255) NOT NULL,
    actor_user_id INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ct2_activity_subject (subject_type, subject_id),
    CONSTRAINT fk_ct2_activity_logs_user
        FOREIGN KEY (actor_user_id) REFERENCES ct2_users (ct2_user_id)
        ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ct2_suppliers (
    ct2_supplier_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    supplier_code VARCHAR(60) NOT NULL UNIQUE,
    supplier_name VARCHAR(190) NOT NULL,
    supplier_type ENUM('supplier', 'partner', 'hybrid') NOT NULL DEFAULT 'supplier',
    primary_contact_name VARCHAR(190) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    phone VARCHAR(60) NOT NULL,
    service_category VARCHAR(120) NOT NULL,
    support_tier ENUM('standard', 'priority', 'strategic') NOT NULL DEFAULT 'standard',
    approval_status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    onboarding_status ENUM('draft', 'in_review', 'approved', 'live', 'blocked') NOT NULL DEFAULT 'draft',
    active_status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    risk_level ENUM('low', 'medium', 'high') NOT NULL DEFAULT 'low',
    internal_owner_user_id INT UNSIGNED NULL,
    external_supplier_id VARCHAR(120) NULL,
    source_system VARCHAR(80) NULL,
    created_by INT UNSIGNED NULL,
    updated_by INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_ct2_suppliers_owner
        FOREIGN KEY (internal_owner_user_id) REFERENCES ct2_users (ct2_user_id)
        ON DELETE SET NULL,
    CONSTRAINT fk_ct2_suppliers_created_by
        FOREIGN KEY (created_by) REFERENCES ct2_users (ct2_user_id)
        ON DELETE SET NULL,
    CONSTRAINT fk_ct2_suppliers_updated_by
        FOREIGN KEY (updated_by) REFERENCES ct2_users (ct2_user_id)
        ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ct2_supplier_contacts (
    ct2_supplier_contact_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ct2_supplier_id INT UNSIGNED NOT NULL,
    contact_name VARCHAR(190) NOT NULL,
    role_title VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL,
    phone VARCHAR(60) NOT NULL,
    is_primary TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_ct2_supplier_contact_primary (ct2_supplier_id, is_primary),
    CONSTRAINT fk_ct2_supplier_contacts_supplier
        FOREIGN KEY (ct2_supplier_id) REFERENCES ct2_suppliers (ct2_supplier_id)
        ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ct2_supplier_onboarding (
    ct2_supplier_onboarding_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ct2_supplier_id INT UNSIGNED NOT NULL,
    checklist_status ENUM('not_started', 'collecting', 'review_ready', 'completed') NOT NULL DEFAULT 'not_started',
    documents_status ENUM('missing', 'partial', 'complete') NOT NULL DEFAULT 'missing',
    compliance_status ENUM('pending', 'cleared', 'flagged') NOT NULL DEFAULT 'pending',
    review_notes TEXT NULL,
    blocked_reason VARCHAR(255) NULL,
    target_go_live_date DATE NULL,
    completed_at DATETIME NULL,
    updated_by INT UNSIGNED NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_ct2_supplier_onboarding_supplier (ct2_supplier_id),
    CONSTRAINT fk_ct2_supplier_onboarding_supplier
        FOREIGN KEY (ct2_supplier_id) REFERENCES ct2_suppliers (ct2_supplier_id)
        ON DELETE CASCADE,
    CONSTRAINT fk_ct2_supplier_onboarding_user
        FOREIGN KEY (updated_by) REFERENCES ct2_users (ct2_user_id)
        ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ct2_supplier_contracts (
    ct2_supplier_contract_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ct2_supplier_id INT UNSIGNED NOT NULL,
    contract_code VARCHAR(80) NOT NULL UNIQUE,
    contract_title VARCHAR(190) NOT NULL,
    effective_date DATE NOT NULL,
    expiry_date DATE NOT NULL,
    renewal_status ENUM('not_started', 'renewal_due', 'renewed', 'expired') NOT NULL DEFAULT 'not_started',
    contract_status ENUM('draft', 'pending_signature', 'active', 'expired', 'terminated') NOT NULL DEFAULT 'draft',
    clause_summary TEXT NULL,
    mock_signature_status ENUM('pending', 'sent', 'signed') NOT NULL DEFAULT 'pending',
    finance_handoff_status ENUM('not_started', 'shared', 'confirmed') NOT NULL DEFAULT 'not_started',
    created_by INT UNSIGNED NULL,
    updated_by INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_ct2_supplier_contracts_supplier
        FOREIGN KEY (ct2_supplier_id) REFERENCES ct2_suppliers (ct2_supplier_id)
        ON DELETE CASCADE,
    CONSTRAINT fk_ct2_supplier_contracts_created_by
        FOREIGN KEY (created_by) REFERENCES ct2_users (ct2_user_id)
        ON DELETE SET NULL,
    CONSTRAINT fk_ct2_supplier_contracts_updated_by
        FOREIGN KEY (updated_by) REFERENCES ct2_users (ct2_user_id)
        ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ct2_supplier_kpis (
    ct2_supplier_kpi_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ct2_supplier_id INT UNSIGNED NOT NULL,
    measurement_date DATE NOT NULL,
    service_score DECIMAL(5,2) NOT NULL,
    delivery_score DECIMAL(5,2) NOT NULL,
    compliance_score DECIMAL(5,2) NOT NULL,
    responsiveness_score DECIMAL(5,2) NOT NULL,
    weighted_score DECIMAL(5,2) NOT NULL,
    risk_flag ENUM('none', 'watch', 'critical') NOT NULL DEFAULT 'none',
    notes TEXT NULL,
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_ct2_supplier_kpis_supplier
        FOREIGN KEY (ct2_supplier_id) REFERENCES ct2_suppliers (ct2_supplier_id)
        ON DELETE CASCADE,
    CONSTRAINT fk_ct2_supplier_kpis_created_by
        FOREIGN KEY (created_by) REFERENCES ct2_users (ct2_user_id)
        ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ct2_supplier_relationship_notes (
    ct2_supplier_relationship_note_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ct2_supplier_id INT UNSIGNED NOT NULL,
    note_type ENUM('communication', 'escalation', 'improvement_plan', 'review') NOT NULL DEFAULT 'communication',
    note_title VARCHAR(190) NOT NULL,
    note_body TEXT NOT NULL,
    next_action_date DATE NULL,
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_ct2_supplier_notes_supplier
        FOREIGN KEY (ct2_supplier_id) REFERENCES ct2_suppliers (ct2_supplier_id)
        ON DELETE CASCADE,
    CONSTRAINT fk_ct2_supplier_notes_created_by
        FOREIGN KEY (created_by) REFERENCES ct2_users (ct2_user_id)
        ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ct2_tour_packages (
    ct2_package_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    package_name VARCHAR(190) NOT NULL UNIQUE,
    base_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    margin_percentage DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_by INT UNSIGNED NULL,
    updated_by INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_ct2_tour_packages_created_by
        FOREIGN KEY (created_by) REFERENCES ct2_users (ct2_user_id)
        ON DELETE SET NULL,
    CONSTRAINT fk_ct2_tour_packages_updated_by
        FOREIGN KEY (updated_by) REFERENCES ct2_users (ct2_user_id)
        ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ct2_inventory_resources (
    ct2_resource_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ct2_supplier_id INT UNSIGNED NOT NULL,
    resource_name VARCHAR(190) NOT NULL,
    resource_type ENUM('transport', 'hotel', 'guide', 'equipment', 'other') NOT NULL DEFAULT 'other',
    capacity INT UNSIGNED NOT NULL DEFAULT 0,
    base_cost DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    status ENUM('available', 'maintenance', 'inactive') NOT NULL DEFAULT 'available',
    notes TEXT NULL,
    created_by INT UNSIGNED NULL,
    updated_by INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_ct2_inventory_resources_supplier
        FOREIGN KEY (ct2_supplier_id) REFERENCES ct2_suppliers (ct2_supplier_id)
        ON DELETE CASCADE,
    CONSTRAINT fk_ct2_inventory_resources_created_by
        FOREIGN KEY (created_by) REFERENCES ct2_users (ct2_user_id)
        ON DELETE SET NULL,
    CONSTRAINT fk_ct2_inventory_resources_updated_by
        FOREIGN KEY (updated_by) REFERENCES ct2_users (ct2_user_id)
        ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ct2_package_resources (
    ct2_package_resource_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ct2_package_id INT UNSIGNED NOT NULL,
    ct2_resource_id INT UNSIGNED NOT NULL,
    units_required INT UNSIGNED NOT NULL DEFAULT 1,
    UNIQUE KEY uniq_ct2_package_resource (ct2_package_id, ct2_resource_id),
    CONSTRAINT fk_ct2_package_resources_package
        FOREIGN KEY (ct2_package_id) REFERENCES ct2_tour_packages (ct2_package_id)
        ON DELETE CASCADE,
    CONSTRAINT fk_ct2_package_resources_resource
        FOREIGN KEY (ct2_resource_id) REFERENCES ct2_inventory_resources (ct2_resource_id)
        ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ct2_resource_allocations (
    ct2_allocation_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ct2_resource_id INT UNSIGNED NOT NULL,
    ct2_package_id INT UNSIGNED NULL,
    external_booking_id VARCHAR(120) NOT NULL,
    allocation_date DATE NOT NULL,
    pax_count INT UNSIGNED NOT NULL DEFAULT 1,
    reserved_units INT UNSIGNED NOT NULL DEFAULT 1,
    allocation_status ENUM('reserved', 'soft_blocked', 'released') NOT NULL DEFAULT 'reserved',
    notes TEXT NULL,
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_ct2_resource_allocations_resource
        FOREIGN KEY (ct2_resource_id) REFERENCES ct2_inventory_resources (ct2_resource_id)
        ON DELETE CASCADE,
    CONSTRAINT fk_ct2_resource_allocations_package
        FOREIGN KEY (ct2_package_id) REFERENCES ct2_tour_packages (ct2_package_id)
        ON DELETE SET NULL,
    CONSTRAINT fk_ct2_resource_allocations_created_by
        FOREIGN KEY (created_by) REFERENCES ct2_users (ct2_user_id)
        ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ct2_seasonal_blocks (
    ct2_block_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ct2_resource_id INT UNSIGNED NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    reason VARCHAR(190) NOT NULL,
    block_type ENUM('maintenance', 'peak_hold', 'supplier_hold', 'manual_soft_block') NOT NULL DEFAULT 'manual_soft_block',
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_ct2_seasonal_blocks_resource
        FOREIGN KEY (ct2_resource_id) REFERENCES ct2_inventory_resources (ct2_resource_id)
        ON DELETE CASCADE,
    CONSTRAINT fk_ct2_seasonal_blocks_created_by
        FOREIGN KEY (created_by) REFERENCES ct2_users (ct2_user_id)
        ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ct2_dispatch_vehicles (
    ct2_vehicle_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    plate_number VARCHAR(60) NOT NULL UNIQUE,
    model_name VARCHAR(120) NOT NULL,
    capacity INT UNSIGNED NOT NULL DEFAULT 0,
    current_mileage INT UNSIGNED NOT NULL DEFAULT 0,
    status ENUM('available', 'maintenance', 'inactive') NOT NULL DEFAULT 'available',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ct2_dispatch_drivers (
    ct2_driver_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(190) NOT NULL,
    license_expiry DATE NOT NULL,
    status ENUM('available', 'assigned', 'inactive') NOT NULL DEFAULT 'available',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ct2_dispatch_orders (
    ct2_dispatch_order_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ct2_allocation_id INT UNSIGNED NULL,
    ct2_vehicle_id INT UNSIGNED NOT NULL,
    ct2_driver_id INT UNSIGNED NOT NULL,
    dispatch_date DATE NOT NULL,
    dispatch_time DATETIME NOT NULL,
    return_time DATETIME NULL,
    start_mileage INT UNSIGNED NOT NULL DEFAULT 0,
    end_mileage INT UNSIGNED NULL,
    dispatch_status ENUM('scheduled', 'en_route', 'completed', 'cancelled') NOT NULL DEFAULT 'scheduled',
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_ct2_dispatch_orders_allocation
        FOREIGN KEY (ct2_allocation_id) REFERENCES ct2_resource_allocations (ct2_allocation_id)
        ON DELETE SET NULL,
    CONSTRAINT fk_ct2_dispatch_orders_vehicle
        FOREIGN KEY (ct2_vehicle_id) REFERENCES ct2_dispatch_vehicles (ct2_vehicle_id)
        ON DELETE CASCADE,
    CONSTRAINT fk_ct2_dispatch_orders_driver
        FOREIGN KEY (ct2_driver_id) REFERENCES ct2_dispatch_drivers (ct2_driver_id)
        ON DELETE CASCADE,
    CONSTRAINT fk_ct2_dispatch_orders_created_by
        FOREIGN KEY (created_by) REFERENCES ct2_users (ct2_user_id)
        ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ct2_maintenance_logs (
    ct2_maintenance_log_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ct2_vehicle_id INT UNSIGNED NOT NULL,
    service_date DATE NOT NULL,
    service_type VARCHAR(120) NOT NULL,
    mechanic_notes TEXT NULL,
    cost DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_ct2_maintenance_logs_vehicle
        FOREIGN KEY (ct2_vehicle_id) REFERENCES ct2_dispatch_vehicles (ct2_vehicle_id)
        ON DELETE CASCADE,
    CONSTRAINT fk_ct2_maintenance_logs_created_by
        FOREIGN KEY (created_by) REFERENCES ct2_users (ct2_user_id)
        ON DELETE SET NULL
) ENGINE=InnoDB;

INSERT INTO ct2_roles (role_key, role_name, description)
VALUES
    ('system_admin', 'System Admin', 'Full CT2 platform administration'),
    ('back_office_manager', 'Back Office Manager', 'Operational oversight and approvals'),
    ('team_lead', 'Team Lead', 'Team supervision and queue management'),
    ('front_desk_agent', 'Front Desk Agent', 'Daily agent support operations'),
    ('accounting_staff', 'Accounting Staff', 'Finance-aligned CT2 operations')
ON DUPLICATE KEY UPDATE
    role_name = VALUES(role_name),
    description = VALUES(description);

INSERT INTO ct2_role_permissions (ct2_role_id, permission_key)
SELECT r.ct2_role_id, permission_key
FROM (
    SELECT 'system_admin' AS role_key, 'dashboard.view' AS permission_key
    UNION ALL SELECT 'system_admin', 'agents.view'
    UNION ALL SELECT 'system_admin', 'agents.manage'
    UNION ALL SELECT 'system_admin', 'agents.approve'
    UNION ALL SELECT 'system_admin', 'staff.view'
    UNION ALL SELECT 'system_admin', 'staff.manage'
    UNION ALL SELECT 'system_admin', 'assignments.manage'
    UNION ALL SELECT 'system_admin', 'approvals.view'
    UNION ALL SELECT 'system_admin', 'approvals.decide'
    UNION ALL SELECT 'system_admin', 'suppliers.view'
    UNION ALL SELECT 'system_admin', 'suppliers.manage'
    UNION ALL SELECT 'system_admin', 'suppliers.approve'
    UNION ALL SELECT 'system_admin', 'availability.view'
    UNION ALL SELECT 'system_admin', 'availability.manage'
    UNION ALL SELECT 'system_admin', 'availability.dispatch'
    UNION ALL SELECT 'system_admin', 'api.access'
    UNION ALL SELECT 'back_office_manager', 'dashboard.view'
    UNION ALL SELECT 'back_office_manager', 'agents.view'
    UNION ALL SELECT 'back_office_manager', 'agents.manage'
    UNION ALL SELECT 'back_office_manager', 'agents.approve'
    UNION ALL SELECT 'back_office_manager', 'staff.view'
    UNION ALL SELECT 'back_office_manager', 'staff.manage'
    UNION ALL SELECT 'back_office_manager', 'assignments.manage'
    UNION ALL SELECT 'back_office_manager', 'approvals.view'
    UNION ALL SELECT 'back_office_manager', 'approvals.decide'
    UNION ALL SELECT 'back_office_manager', 'suppliers.view'
    UNION ALL SELECT 'back_office_manager', 'suppliers.manage'
    UNION ALL SELECT 'back_office_manager', 'suppliers.approve'
    UNION ALL SELECT 'back_office_manager', 'availability.view'
    UNION ALL SELECT 'back_office_manager', 'availability.manage'
    UNION ALL SELECT 'back_office_manager', 'availability.dispatch'
    UNION ALL SELECT 'back_office_manager', 'api.access'
    UNION ALL SELECT 'team_lead', 'dashboard.view'
    UNION ALL SELECT 'team_lead', 'agents.view'
    UNION ALL SELECT 'team_lead', 'staff.view'
    UNION ALL SELECT 'team_lead', 'assignments.manage'
    UNION ALL SELECT 'team_lead', 'approvals.view'
    UNION ALL SELECT 'team_lead', 'suppliers.view'
    UNION ALL SELECT 'team_lead', 'suppliers.manage'
    UNION ALL SELECT 'team_lead', 'availability.view'
    UNION ALL SELECT 'team_lead', 'availability.manage'
    UNION ALL SELECT 'front_desk_agent', 'dashboard.view'
    UNION ALL SELECT 'front_desk_agent', 'agents.view'
    UNION ALL SELECT 'front_desk_agent', 'staff.view'
    UNION ALL SELECT 'front_desk_agent', 'suppliers.view'
    UNION ALL SELECT 'front_desk_agent', 'availability.view'
    UNION ALL SELECT 'accounting_staff', 'dashboard.view'
    UNION ALL SELECT 'accounting_staff', 'approvals.view'
    UNION ALL SELECT 'accounting_staff', 'suppliers.view'
    UNION ALL SELECT 'accounting_staff', 'api.access'
) AS permission_seed
INNER JOIN ct2_roles AS r ON r.role_key = permission_seed.role_key
ON DUPLICATE KEY UPDATE permission_key = VALUES(permission_key);

INSERT INTO ct2_users (username, email, password_hash, display_name, is_active)
VALUES (
    'ct2admin',
    'ct2admin@example.com',
    '$2y$12$Ntbg7JaaJr34rIGv4xMhvOpbXMRSY0U0ODlHXEQPORTpQq0OUpWdO',
    'CT2 System Administrator',
    1
)
ON DUPLICATE KEY UPDATE
    display_name = VALUES(display_name),
    is_active = VALUES(is_active);

INSERT INTO ct2_user_roles (ct2_user_id, ct2_role_id)
SELECT u.ct2_user_id, r.ct2_role_id
FROM ct2_users AS u
INNER JOIN ct2_roles AS r ON r.role_key = 'system_admin'
WHERE u.username = 'ct2admin'
ON DUPLICATE KEY UPDATE assigned_at = CURRENT_TIMESTAMP;
