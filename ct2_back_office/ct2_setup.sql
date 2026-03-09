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
    file_size_bytes INT UNSIGNED NOT NULL DEFAULT 0,
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

CREATE TABLE IF NOT EXISTS ct2_campaigns (
    ct2_campaign_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    campaign_code VARCHAR(80) NOT NULL UNIQUE,
    campaign_name VARCHAR(190) NOT NULL,
    campaign_type ENUM('seasonal', 'partner', 'voucher', 'affiliate', 'brand', 'other') NOT NULL DEFAULT 'other',
    channel_type ENUM('email', 'social', 'search', 'direct', 'affiliate', 'hybrid') NOT NULL DEFAULT 'hybrid',
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    budget_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    status ENUM('draft', 'pending_approval', 'active', 'paused', 'completed', 'archived') NOT NULL DEFAULT 'pending_approval',
    approval_status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    target_audience VARCHAR(255) NULL,
    external_customer_segment_id VARCHAR(120) NULL,
    source_system VARCHAR(80) NULL,
    created_by INT UNSIGNED NULL,
    updated_by INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_ct2_campaigns_created_by
        FOREIGN KEY (created_by) REFERENCES ct2_users (ct2_user_id)
        ON DELETE SET NULL,
    CONSTRAINT fk_ct2_campaigns_updated_by
        FOREIGN KEY (updated_by) REFERENCES ct2_users (ct2_user_id)
        ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ct2_promotions (
    ct2_promotion_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ct2_campaign_id INT UNSIGNED NOT NULL,
    promotion_code VARCHAR(80) NOT NULL UNIQUE,
    promotion_name VARCHAR(190) NOT NULL,
    promotion_type ENUM('percentage', 'fixed_amount', 'bundle', 'referral', 'loyalty', 'manual') NOT NULL DEFAULT 'percentage',
    discount_value DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    eligibility_rule TEXT NULL,
    valid_from DATE NOT NULL,
    valid_until DATE NOT NULL,
    usage_limit INT UNSIGNED NOT NULL DEFAULT 1,
    promotion_status ENUM('draft', 'pending_approval', 'active', 'paused', 'expired', 'archived') NOT NULL DEFAULT 'pending_approval',
    approval_status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    external_booking_scope VARCHAR(120) NULL,
    source_system VARCHAR(80) NULL,
    created_by INT UNSIGNED NULL,
    updated_by INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_ct2_promotions_campaign
        FOREIGN KEY (ct2_campaign_id) REFERENCES ct2_campaigns (ct2_campaign_id)
        ON DELETE CASCADE,
    CONSTRAINT fk_ct2_promotions_created_by
        FOREIGN KEY (created_by) REFERENCES ct2_users (ct2_user_id)
        ON DELETE SET NULL,
    CONSTRAINT fk_ct2_promotions_updated_by
        FOREIGN KEY (updated_by) REFERENCES ct2_users (ct2_user_id)
        ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ct2_vouchers (
    ct2_voucher_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ct2_promotion_id INT UNSIGNED NULL,
    voucher_code VARCHAR(80) NOT NULL UNIQUE,
    voucher_name VARCHAR(190) NOT NULL,
    customer_scope ENUM('single_use', 'multi_use', 'affiliate', 'open') NOT NULL DEFAULT 'single_use',
    max_redemptions INT UNSIGNED NOT NULL DEFAULT 1,
    redeemed_count INT UNSIGNED NOT NULL DEFAULT 0,
    voucher_status ENUM('issued', 'active', 'redeemed', 'expired', 'cancelled') NOT NULL DEFAULT 'issued',
    valid_from DATE NOT NULL,
    valid_until DATE NOT NULL,
    external_customer_id VARCHAR(120) NULL,
    source_system VARCHAR(80) NULL,
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_ct2_vouchers_promotion
        FOREIGN KEY (ct2_promotion_id) REFERENCES ct2_promotions (ct2_promotion_id)
        ON DELETE SET NULL,
    CONSTRAINT fk_ct2_vouchers_created_by
        FOREIGN KEY (created_by) REFERENCES ct2_users (ct2_user_id)
        ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ct2_affiliates (
    ct2_affiliate_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    affiliate_code VARCHAR(80) NOT NULL UNIQUE,
    affiliate_name VARCHAR(190) NOT NULL,
    contact_name VARCHAR(190) NOT NULL,
    email VARCHAR(190) NOT NULL,
    phone VARCHAR(60) NOT NULL,
    affiliate_status ENUM('onboarding', 'active', 'paused', 'inactive') NOT NULL DEFAULT 'onboarding',
    commission_rate DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    payout_status ENUM('pending_setup', 'ready', 'hold') NOT NULL DEFAULT 'pending_setup',
    referral_code VARCHAR(80) NOT NULL UNIQUE,
    external_partner_id VARCHAR(120) NULL,
    source_system VARCHAR(80) NULL,
    created_by INT UNSIGNED NULL,
    updated_by INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_ct2_affiliates_created_by
        FOREIGN KEY (created_by) REFERENCES ct2_users (ct2_user_id)
        ON DELETE SET NULL,
    CONSTRAINT fk_ct2_affiliates_updated_by
        FOREIGN KEY (updated_by) REFERENCES ct2_users (ct2_user_id)
        ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ct2_referral_clicks (
    ct2_referral_click_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ct2_affiliate_id INT UNSIGNED NOT NULL,
    ct2_campaign_id INT UNSIGNED NULL,
    referral_code VARCHAR(80) NOT NULL,
    click_date DATETIME NOT NULL,
    landing_page VARCHAR(255) NULL,
    external_customer_id VARCHAR(120) NULL,
    external_booking_id VARCHAR(120) NULL,
    attribution_status ENUM('clicked', 'qualified', 'booked', 'lost') NOT NULL DEFAULT 'clicked',
    source_system VARCHAR(80) NULL,
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_ct2_referral_clicks_affiliate
        FOREIGN KEY (ct2_affiliate_id) REFERENCES ct2_affiliates (ct2_affiliate_id)
        ON DELETE CASCADE,
    CONSTRAINT fk_ct2_referral_clicks_campaign
        FOREIGN KEY (ct2_campaign_id) REFERENCES ct2_campaigns (ct2_campaign_id)
        ON DELETE SET NULL,
    CONSTRAINT fk_ct2_referral_clicks_created_by
        FOREIGN KEY (created_by) REFERENCES ct2_users (ct2_user_id)
        ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ct2_redemption_logs (
    ct2_redemption_log_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ct2_campaign_id INT UNSIGNED NULL,
    ct2_promotion_id INT UNSIGNED NULL,
    ct2_voucher_id INT UNSIGNED NULL,
    redemption_date DATETIME NOT NULL,
    external_customer_id VARCHAR(120) NULL,
    external_booking_id VARCHAR(120) NULL,
    redeemed_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    redemption_status ENUM('pending', 'redeemed', 'reversed', 'expired') NOT NULL DEFAULT 'pending',
    source_system VARCHAR(80) NULL,
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_ct2_redemption_logs_campaign
        FOREIGN KEY (ct2_campaign_id) REFERENCES ct2_campaigns (ct2_campaign_id)
        ON DELETE SET NULL,
    CONSTRAINT fk_ct2_redemption_logs_promotion
        FOREIGN KEY (ct2_promotion_id) REFERENCES ct2_promotions (ct2_promotion_id)
        ON DELETE SET NULL,
    CONSTRAINT fk_ct2_redemption_logs_voucher
        FOREIGN KEY (ct2_voucher_id) REFERENCES ct2_vouchers (ct2_voucher_id)
        ON DELETE SET NULL,
    CONSTRAINT fk_ct2_redemption_logs_created_by
        FOREIGN KEY (created_by) REFERENCES ct2_users (ct2_user_id)
        ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ct2_campaign_metrics (
    ct2_campaign_metric_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ct2_campaign_id INT UNSIGNED NOT NULL,
    report_date DATE NOT NULL,
    impressions_count INT UNSIGNED NOT NULL DEFAULT 0,
    click_count INT UNSIGNED NOT NULL DEFAULT 0,
    lead_count INT UNSIGNED NOT NULL DEFAULT 0,
    conversion_count INT UNSIGNED NOT NULL DEFAULT 0,
    attributed_revenue DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    positive_reviews INT UNSIGNED NOT NULL DEFAULT 0,
    neutral_reviews INT UNSIGNED NOT NULL DEFAULT 0,
    negative_reviews INT UNSIGNED NOT NULL DEFAULT 0,
    external_review_batch_id VARCHAR(120) NULL,
    source_system VARCHAR(80) NULL,
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_ct2_campaign_metrics_campaign
        FOREIGN KEY (ct2_campaign_id) REFERENCES ct2_campaigns (ct2_campaign_id)
        ON DELETE CASCADE,
    CONSTRAINT fk_ct2_campaign_metrics_created_by
        FOREIGN KEY (created_by) REFERENCES ct2_users (ct2_user_id)
        ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ct2_marketing_notes (
    ct2_marketing_note_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ct2_campaign_id INT UNSIGNED NULL,
    ct2_affiliate_id INT UNSIGNED NULL,
    note_type ENUM('performance', 'partner_follow_up', 'review_summary', 'risk', 'handoff') NOT NULL DEFAULT 'performance',
    note_title VARCHAR(190) NOT NULL,
    note_body TEXT NOT NULL,
    next_action_date DATE NULL,
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_ct2_marketing_notes_campaign
        FOREIGN KEY (ct2_campaign_id) REFERENCES ct2_campaigns (ct2_campaign_id)
        ON DELETE SET NULL,
    CONSTRAINT fk_ct2_marketing_notes_affiliate
        FOREIGN KEY (ct2_affiliate_id) REFERENCES ct2_affiliates (ct2_affiliate_id)
        ON DELETE SET NULL,
    CONSTRAINT fk_ct2_marketing_notes_created_by
        FOREIGN KEY (created_by) REFERENCES ct2_users (ct2_user_id)
        ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ct2_visa_types (
    ct2_visa_type_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    visa_code VARCHAR(80) NOT NULL UNIQUE,
    country_name VARCHAR(120) NOT NULL,
    visa_category VARCHAR(120) NOT NULL,
    processing_days INT UNSIGNED NOT NULL DEFAULT 1,
    biometrics_required TINYINT(1) NOT NULL DEFAULT 0,
    validity_period_days INT UNSIGNED NOT NULL DEFAULT 1,
    base_fee DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ct2_visa_applications (
    ct2_visa_application_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ct2_visa_type_id INT UNSIGNED NOT NULL,
    application_reference VARCHAR(100) NOT NULL UNIQUE,
    external_customer_id VARCHAR(120) NOT NULL,
    external_agent_id VARCHAR(120) NULL,
    source_system VARCHAR(80) NULL,
    status ENUM('draft', 'submitted', 'document_review', 'appointment_scheduled', 'processing', 'approved', 'released', 'rejected', 'cancelled', 'escalated_review') NOT NULL DEFAULT 'submitted',
    submission_date DATE NOT NULL,
    appointment_date DATETIME NULL,
    embassy_reference VARCHAR(120) NULL,
    approval_status ENUM('not_required', 'pending', 'approved', 'rejected') NOT NULL DEFAULT 'not_required',
    documents_verified TINYINT(1) NOT NULL DEFAULT 0,
    outstanding_item_count INT UNSIGNED NOT NULL DEFAULT 0,
    payment_status ENUM('unpaid', 'partial', 'paid', 'refunded') NOT NULL DEFAULT 'unpaid',
    remarks TEXT NULL,
    created_by INT UNSIGNED NULL,
    updated_by INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_ct2_visa_applications_type
        FOREIGN KEY (ct2_visa_type_id) REFERENCES ct2_visa_types (ct2_visa_type_id)
        ON DELETE CASCADE,
    CONSTRAINT fk_ct2_visa_applications_created_by
        FOREIGN KEY (created_by) REFERENCES ct2_users (ct2_user_id)
        ON DELETE SET NULL,
    CONSTRAINT fk_ct2_visa_applications_updated_by
        FOREIGN KEY (updated_by) REFERENCES ct2_users (ct2_user_id)
        ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ct2_visa_checklist_items (
    ct2_visa_checklist_item_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ct2_visa_type_id INT UNSIGNED NOT NULL,
    item_name VARCHAR(190) NOT NULL,
    item_description TEXT NULL,
    is_mandatory TINYINT(1) NOT NULL DEFAULT 1,
    file_size_limit_mb INT UNSIGNED NOT NULL DEFAULT 1,
    requires_original TINYINT(1) NOT NULL DEFAULT 0,
    display_order INT UNSIGNED NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_ct2_visa_checklist_items_type
        FOREIGN KEY (ct2_visa_type_id) REFERENCES ct2_visa_types (ct2_visa_type_id)
        ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ct2_application_checklist (
    ct2_application_checklist_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ct2_visa_application_id INT UNSIGNED NOT NULL,
    ct2_visa_checklist_item_id INT UNSIGNED NOT NULL,
    checklist_status ENUM('pending', 'submitted', 'verified', 'rejected', 'waived') NOT NULL DEFAULT 'pending',
    verification_notes TEXT NULL,
    ct2_document_id INT UNSIGNED NULL,
    verified_by INT UNSIGNED NULL,
    verified_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_ct2_application_checklist (ct2_visa_application_id, ct2_visa_checklist_item_id),
    CONSTRAINT fk_ct2_application_checklist_application
        FOREIGN KEY (ct2_visa_application_id) REFERENCES ct2_visa_applications (ct2_visa_application_id)
        ON DELETE CASCADE,
    CONSTRAINT fk_ct2_application_checklist_item
        FOREIGN KEY (ct2_visa_checklist_item_id) REFERENCES ct2_visa_checklist_items (ct2_visa_checklist_item_id)
        ON DELETE CASCADE,
    CONSTRAINT fk_ct2_application_checklist_document
        FOREIGN KEY (ct2_document_id) REFERENCES ct2_documents (ct2_document_id)
        ON DELETE SET NULL,
    CONSTRAINT fk_ct2_application_checklist_verified_by
        FOREIGN KEY (verified_by) REFERENCES ct2_users (ct2_user_id)
        ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ct2_visa_payments (
    ct2_visa_payment_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ct2_visa_application_id INT UNSIGNED NOT NULL,
    payment_reference VARCHAR(100) NOT NULL,
    external_payment_id VARCHAR(120) NULL,
    amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    currency VARCHAR(10) NOT NULL DEFAULT 'PHP',
    payment_method VARCHAR(80) NOT NULL DEFAULT 'Manual',
    payment_status ENUM('pending', 'completed', 'refunded', 'voided') NOT NULL DEFAULT 'pending',
    paid_at DATETIME NULL,
    source_system VARCHAR(80) NULL,
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_ct2_visa_payments_application
        FOREIGN KEY (ct2_visa_application_id) REFERENCES ct2_visa_applications (ct2_visa_application_id)
        ON DELETE CASCADE,
    CONSTRAINT fk_ct2_visa_payments_created_by
        FOREIGN KEY (created_by) REFERENCES ct2_users (ct2_user_id)
        ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ct2_notification_logs (
    ct2_notification_log_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ct2_visa_application_id INT UNSIGNED NOT NULL,
    notification_channel ENUM('email', 'sms', 'portal', 'manual') NOT NULL DEFAULT 'email',
    recipient_reference VARCHAR(190) NOT NULL,
    notification_subject VARCHAR(190) NOT NULL,
    notification_message TEXT NOT NULL,
    delivery_status ENUM('queued', 'sent', 'failed') NOT NULL DEFAULT 'queued',
    sent_at DATETIME NULL,
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_ct2_notification_logs_application
        FOREIGN KEY (ct2_visa_application_id) REFERENCES ct2_visa_applications (ct2_visa_application_id)
        ON DELETE CASCADE,
    CONSTRAINT fk_ct2_notification_logs_created_by
        FOREIGN KEY (created_by) REFERENCES ct2_users (ct2_user_id)
        ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ct2_visa_notes (
    ct2_visa_note_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ct2_visa_application_id INT UNSIGNED NOT NULL,
    note_type ENUM('review', 'client_update', 'risk', 'embassy', 'payment') NOT NULL DEFAULT 'review',
    note_body TEXT NOT NULL,
    next_action_date DATE NULL,
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_ct2_visa_notes_application
        FOREIGN KEY (ct2_visa_application_id) REFERENCES ct2_visa_applications (ct2_visa_application_id)
        ON DELETE CASCADE,
    CONSTRAINT fk_ct2_visa_notes_created_by
        FOREIGN KEY (created_by) REFERENCES ct2_users (ct2_user_id)
        ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ct2_financial_reports (
    ct2_financial_report_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    report_code VARCHAR(80) NOT NULL UNIQUE,
    report_name VARCHAR(190) NOT NULL,
    report_scope ENUM('agents', 'suppliers', 'availability', 'marketing', 'visa', 'cross_module') NOT NULL DEFAULT 'cross_module',
    report_status ENUM('draft', 'active', 'archived') NOT NULL DEFAULT 'active',
    default_date_range ENUM('7d', '30d', '90d', 'custom') NOT NULL DEFAULT '30d',
    definition_notes TEXT NULL,
    created_by INT UNSIGNED NULL,
    updated_by INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_ct2_financial_reports_created_by
        FOREIGN KEY (created_by) REFERENCES ct2_users (ct2_user_id)
        ON DELETE SET NULL,
    CONSTRAINT fk_ct2_financial_reports_updated_by
        FOREIGN KEY (updated_by) REFERENCES ct2_users (ct2_user_id)
        ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ct2_report_filters (
    ct2_report_filter_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ct2_financial_report_id INT UNSIGNED NOT NULL,
    filter_key VARCHAR(120) NOT NULL,
    filter_label VARCHAR(190) NOT NULL,
    filter_type ENUM('date', 'select', 'text', 'status') NOT NULL DEFAULT 'text',
    default_value VARCHAR(255) NULL,
    sort_order INT UNSIGNED NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_ct2_report_filter (ct2_financial_report_id, filter_key),
    CONSTRAINT fk_ct2_report_filters_report
        FOREIGN KEY (ct2_financial_report_id) REFERENCES ct2_financial_reports (ct2_financial_report_id)
        ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ct2_report_runs (
    ct2_report_run_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ct2_financial_report_id INT UNSIGNED NOT NULL,
    run_label VARCHAR(190) NOT NULL,
    date_from DATE NOT NULL,
    date_to DATE NOT NULL,
    module_key VARCHAR(80) NOT NULL DEFAULT 'all',
    source_system VARCHAR(80) NULL,
    generated_by INT UNSIGNED NULL,
    generated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ct2_report_runs_report (ct2_financial_report_id, generated_at),
    CONSTRAINT fk_ct2_report_runs_report
        FOREIGN KEY (ct2_financial_report_id) REFERENCES ct2_financial_reports (ct2_financial_report_id)
        ON DELETE CASCADE,
    CONSTRAINT fk_ct2_report_runs_generated_by
        FOREIGN KEY (generated_by) REFERENCES ct2_users (ct2_user_id)
        ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ct2_financial_snapshots (
    ct2_financial_snapshot_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ct2_report_run_id INT UNSIGNED NOT NULL,
    snapshot_type VARCHAR(120) NOT NULL,
    reference_code VARCHAR(120) NOT NULL,
    source_module VARCHAR(80) NOT NULL,
    source_record_id INT UNSIGNED NULL,
    metric_label VARCHAR(120) NOT NULL,
    metric_value DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    metric_count INT UNSIGNED NOT NULL DEFAULT 0,
    status_flag ENUM('ok', 'warning', 'critical') NOT NULL DEFAULT 'ok',
    external_reference_id VARCHAR(120) NULL,
    notes TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ct2_financial_snapshots_run (ct2_report_run_id, source_module),
    CONSTRAINT fk_ct2_financial_snapshots_run
        FOREIGN KEY (ct2_report_run_id) REFERENCES ct2_report_runs (ct2_report_run_id)
        ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ct2_reconciliation_flags (
    ct2_reconciliation_flag_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ct2_report_run_id INT UNSIGNED NOT NULL,
    flag_type VARCHAR(120) NOT NULL,
    source_module VARCHAR(80) NOT NULL,
    source_record_id INT UNSIGNED NULL,
    severity ENUM('low', 'medium', 'high') NOT NULL DEFAULT 'medium',
    flag_status ENUM('open', 'acknowledged', 'resolved') NOT NULL DEFAULT 'open',
    flag_summary VARCHAR(255) NOT NULL,
    resolution_notes TEXT NULL,
    resolved_by INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    resolved_at DATETIME NULL,
    INDEX idx_ct2_reconciliation_flags_run (ct2_report_run_id, flag_status),
    CONSTRAINT fk_ct2_reconciliation_flags_run
        FOREIGN KEY (ct2_report_run_id) REFERENCES ct2_report_runs (ct2_report_run_id)
        ON DELETE CASCADE,
    CONSTRAINT fk_ct2_reconciliation_flags_resolved_by
        FOREIGN KEY (resolved_by) REFERENCES ct2_users (ct2_user_id)
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
    UNION ALL SELECT 'system_admin', 'marketing.view'
    UNION ALL SELECT 'system_admin', 'marketing.manage'
    UNION ALL SELECT 'system_admin', 'marketing.approve'
    UNION ALL SELECT 'system_admin', 'financial.view'
    UNION ALL SELECT 'system_admin', 'financial.manage'
    UNION ALL SELECT 'system_admin', 'financial.export'
    UNION ALL SELECT 'system_admin', 'visa.view'
    UNION ALL SELECT 'system_admin', 'visa.manage'
    UNION ALL SELECT 'system_admin', 'visa.approve'
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
    UNION ALL SELECT 'back_office_manager', 'marketing.view'
    UNION ALL SELECT 'back_office_manager', 'marketing.manage'
    UNION ALL SELECT 'back_office_manager', 'marketing.approve'
    UNION ALL SELECT 'back_office_manager', 'financial.view'
    UNION ALL SELECT 'back_office_manager', 'financial.manage'
    UNION ALL SELECT 'back_office_manager', 'financial.export'
    UNION ALL SELECT 'back_office_manager', 'visa.view'
    UNION ALL SELECT 'back_office_manager', 'visa.manage'
    UNION ALL SELECT 'back_office_manager', 'visa.approve'
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
    UNION ALL SELECT 'team_lead', 'marketing.view'
    UNION ALL SELECT 'team_lead', 'marketing.manage'
    UNION ALL SELECT 'team_lead', 'financial.view'
    UNION ALL SELECT 'team_lead', 'visa.view'
    UNION ALL SELECT 'team_lead', 'visa.manage'
    UNION ALL SELECT 'front_desk_agent', 'dashboard.view'
    UNION ALL SELECT 'front_desk_agent', 'agents.view'
    UNION ALL SELECT 'front_desk_agent', 'staff.view'
    UNION ALL SELECT 'front_desk_agent', 'suppliers.view'
    UNION ALL SELECT 'front_desk_agent', 'availability.view'
    UNION ALL SELECT 'front_desk_agent', 'marketing.view'
    UNION ALL SELECT 'front_desk_agent', 'visa.view'
    UNION ALL SELECT 'front_desk_agent', 'visa.manage'
    UNION ALL SELECT 'accounting_staff', 'dashboard.view'
    UNION ALL SELECT 'accounting_staff', 'approvals.view'
    UNION ALL SELECT 'accounting_staff', 'suppliers.view'
    UNION ALL SELECT 'accounting_staff', 'marketing.view'
    UNION ALL SELECT 'accounting_staff', 'financial.view'
    UNION ALL SELECT 'accounting_staff', 'financial.manage'
    UNION ALL SELECT 'accounting_staff', 'financial.export'
    UNION ALL SELECT 'accounting_staff', 'visa.view'
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

INSERT INTO ct2_financial_reports (
    report_code, report_name, report_scope, report_status, default_date_range, definition_notes
)
VALUES
    ('CT2-OPS-001', 'CT2 Operational Financial Snapshot', 'cross_module', 'active', '30d', 'Cross-module operational margin and reconciliation summary.'),
    ('CT2-AGENT-001', 'Agent Commission Exposure', 'agents', 'active', '30d', 'Tracks approved agents, commission rates, and missing finance references.'),
    ('CT2-SUP-001', 'Supplier Cost Exposure', 'suppliers', 'active', '30d', 'Tracks supplier-linked resource cost exposure and contract renewal risk.'),
    ('CT2-AVL-001', 'Resource Margin Monitor', 'availability', 'active', '30d', 'Tracks package margin against current linked resource costs.'),
    ('CT2-MKT-001', 'Marketing ROI Monitor', 'marketing', 'active', '30d', 'Tracks budget versus attributed revenue and unresolved redemption gaps.'),
    ('CT2-VISA-001', 'Visa Payment Monitor', 'visa', 'active', '30d', 'Tracks visa fee coverage and open unpaid applications.')
ON DUPLICATE KEY UPDATE
    report_name = VALUES(report_name),
    report_scope = VALUES(report_scope),
    report_status = VALUES(report_status),
    default_date_range = VALUES(default_date_range),
    definition_notes = VALUES(definition_notes);

INSERT INTO ct2_report_filters (
    ct2_financial_report_id, filter_key, filter_label, filter_type, default_value, sort_order
)
SELECT fr.ct2_financial_report_id, fs.filter_key, fs.filter_label, fs.filter_type, fs.default_value, fs.sort_order
FROM (
    SELECT 'CT2-OPS-001' AS report_code, 'date_from' AS filter_key, 'Date From' AS filter_label, 'date' AS filter_type, NULL AS default_value, 1 AS sort_order
    UNION ALL SELECT 'CT2-OPS-001', 'date_to', 'Date To', 'date', NULL, 2
    UNION ALL SELECT 'CT2-OPS-001', 'module_key', 'Module Scope', 'select', 'all', 3
    UNION ALL SELECT 'CT2-OPS-001', 'source_system', 'Source System', 'text', NULL, 4
    UNION ALL SELECT 'CT2-AGENT-001', 'date_from', 'Date From', 'date', NULL, 1
    UNION ALL SELECT 'CT2-AGENT-001', 'date_to', 'Date To', 'date', NULL, 2
    UNION ALL SELECT 'CT2-AGENT-001', 'source_system', 'Source System', 'text', NULL, 3
    UNION ALL SELECT 'CT2-SUP-001', 'date_from', 'Date From', 'date', NULL, 1
    UNION ALL SELECT 'CT2-SUP-001', 'date_to', 'Date To', 'date', NULL, 2
    UNION ALL SELECT 'CT2-SUP-001', 'source_system', 'Source System', 'text', NULL, 3
    UNION ALL SELECT 'CT2-AVL-001', 'date_from', 'Date From', 'date', NULL, 1
    UNION ALL SELECT 'CT2-AVL-001', 'date_to', 'Date To', 'date', NULL, 2
    UNION ALL SELECT 'CT2-MKT-001', 'date_from', 'Date From', 'date', NULL, 1
    UNION ALL SELECT 'CT2-MKT-001', 'date_to', 'Date To', 'date', NULL, 2
    UNION ALL SELECT 'CT2-MKT-001', 'source_system', 'Source System', 'text', NULL, 3
    UNION ALL SELECT 'CT2-VISA-001', 'date_from', 'Date From', 'date', NULL, 1
    UNION ALL SELECT 'CT2-VISA-001', 'date_to', 'Date To', 'date', NULL, 2
    UNION ALL SELECT 'CT2-VISA-001', 'payment_status', 'Payment Status', 'status', 'unpaid', 3
) AS fs
INNER JOIN ct2_financial_reports AS fr ON fr.report_code = fs.report_code
ON DUPLICATE KEY UPDATE
    filter_label = VALUES(filter_label),
    filter_type = VALUES(filter_type),
    default_value = VALUES(default_value),
    sort_order = VALUES(sort_order);

INSERT INTO ct2_users (username, email, password_hash, display_name, is_active)
VALUES
    ('ct2manager', 'ct2manager@example.com', '$2y$12$Ntbg7JaaJr34rIGv4xMhvOpbXMRSY0U0ODlHXEQPORTpQq0OUpWdO', 'CT2 Back-Office Manager', 1),
    ('ct2lead', 'ct2lead@example.com', '$2y$12$Ntbg7JaaJr34rIGv4xMhvOpbXMRSY0U0ODlHXEQPORTpQq0OUpWdO', 'CT2 Team Lead', 1),
    ('ct2desk', 'ct2desk@example.com', '$2y$12$Ntbg7JaaJr34rIGv4xMhvOpbXMRSY0U0ODlHXEQPORTpQq0OUpWdO', 'CT2 Front Desk Agent', 1),
    ('ct2finance', 'ct2finance@example.com', '$2y$12$Ntbg7JaaJr34rIGv4xMhvOpbXMRSY0U0ODlHXEQPORTpQq0OUpWdO', 'CT2 Accounting Staff', 1)
ON DUPLICATE KEY UPDATE
    display_name = VALUES(display_name),
    is_active = VALUES(is_active);

INSERT INTO ct2_user_roles (ct2_user_id, ct2_role_id)
SELECT u.ct2_user_id, r.ct2_role_id
FROM ct2_users AS u
INNER JOIN ct2_roles AS r ON r.role_key = 'back_office_manager'
WHERE u.username = 'ct2manager'
ON DUPLICATE KEY UPDATE assigned_at = CURRENT_TIMESTAMP;

INSERT INTO ct2_user_roles (ct2_user_id, ct2_role_id)
SELECT u.ct2_user_id, r.ct2_role_id
FROM ct2_users AS u
INNER JOIN ct2_roles AS r ON r.role_key = 'team_lead'
WHERE u.username = 'ct2lead'
ON DUPLICATE KEY UPDATE assigned_at = CURRENT_TIMESTAMP;

INSERT INTO ct2_user_roles (ct2_user_id, ct2_role_id)
SELECT u.ct2_user_id, r.ct2_role_id
FROM ct2_users AS u
INNER JOIN ct2_roles AS r ON r.role_key = 'front_desk_agent'
WHERE u.username = 'ct2desk'
ON DUPLICATE KEY UPDATE assigned_at = CURRENT_TIMESTAMP;

INSERT INTO ct2_user_roles (ct2_user_id, ct2_role_id)
SELECT u.ct2_user_id, r.ct2_role_id
FROM ct2_users AS u
INNER JOIN ct2_roles AS r ON r.role_key = 'accounting_staff'
WHERE u.username = 'ct2finance'
ON DUPLICATE KEY UPDATE assigned_at = CURRENT_TIMESTAMP;

INSERT INTO ct2_staff (
    ct2_user_id, staff_code, full_name, email, phone, department, position_title,
    employment_status, availability_status, team_name, notes, created_by, updated_by
)
SELECT
    u.ct2_user_id,
    seed.staff_code,
    seed.full_name,
    seed.email,
    seed.phone,
    seed.department,
    seed.position_title,
    seed.employment_status,
    seed.availability_status,
    seed.team_name,
    seed.notes,
    admin.ct2_user_id,
    admin.ct2_user_id
FROM (
    SELECT 'ct2manager' AS username, 'STF-CT2-001' AS staff_code, 'Patricia Dela Cruz' AS full_name, 'ct2manager@example.com' AS email, '+63-917-100-0001' AS phone, 'Back Office Operations' AS department, 'Back-Office Manager' AS position_title, 'active' AS employment_status, 'available' AS availability_status, 'Operations Control' AS team_name, 'Primary approver account for QA workflows.' AS notes
    UNION ALL SELECT 'ct2lead', 'STF-CT2-002', 'Marco Santos', 'ct2lead@example.com', '+63-917-100-0002', 'Supplier and Availability', 'Team Lead', 'active', 'busy', 'Resource Planning', 'Used for assignment and ownership walkthroughs.'
    UNION ALL SELECT 'ct2desk', 'STF-CT2-003', 'Lia Fernandez', 'ct2desk@example.com', '+63-917-100-0003', 'Client Services', 'Front Desk Coordinator', 'active', 'available', 'Client Fulfillment', 'Used for visa and customer-facing intake QA.'
    UNION ALL SELECT 'ct2finance', 'STF-CT2-004', 'Noel Reyes', 'ct2finance@example.com', '+63-917-100-0004', 'Finance Coordination', 'Accounting Analyst', 'active', 'available', 'Finance Handoff', 'Used for reconciliation and export QA.'
) AS seed
INNER JOIN ct2_users AS u ON u.username = seed.username
INNER JOIN ct2_users AS admin ON admin.username = 'ct2admin'
WHERE NOT EXISTS (
    SELECT 1
    FROM ct2_staff AS s
    WHERE s.staff_code = seed.staff_code
);

INSERT INTO ct2_agents (
    agent_code, agency_name, contact_person, email, phone, region, commission_rate,
    support_level, approval_status, active_status, external_booking_id, external_customer_id,
    external_payment_id, source_system, created_by, updated_by
)
SELECT
    seed.agent_code,
    seed.agency_name,
    seed.contact_person,
    seed.email,
    seed.phone,
    seed.region,
    seed.commission_rate,
    seed.support_level,
    seed.approval_status,
    seed.active_status,
    seed.external_booking_id,
    seed.external_customer_id,
    seed.external_payment_id,
    seed.source_system,
    admin.ct2_user_id,
    admin.ct2_user_id
FROM (
    SELECT 'AGT-CT2-001' AS agent_code, 'Northbound Trails Travel' AS agency_name, 'Alice Mendoza' AS contact_person, 'alice@northbound.example.com' AS email, '+63-917-200-0001' AS phone, 'North Luzon' AS region, 12.50 AS commission_rate, 'priority' AS support_level, 'approved' AS approval_status, 'active' AS active_status, 'CT1-BKG-1001' AS external_booking_id, 'CT1-CUST-8801' AS external_customer_id, 'FIN-PAY-4401' AS external_payment_id, 'ct1' AS source_system
    UNION ALL SELECT 'AGT-CT2-002', 'Island Connect Tours', 'Ramon Aquino', 'ramon@islandconnect.example.com', '+63-917-200-0002', 'Visayas', 10.00, 'standard', 'pending', 'active', 'CT1-BKG-1002', 'CT1-CUST-8802', 'FIN-PAY-4402', 'ct1'
) AS seed
INNER JOIN ct2_users AS admin ON admin.username = 'ct2admin'
WHERE NOT EXISTS (
    SELECT 1
    FROM ct2_agents AS a
    WHERE a.agent_code = seed.agent_code
);

INSERT INTO ct2_agent_staff_assignments (
    ct2_agent_id, ct2_staff_id, assignment_role, assignment_status, start_date, end_date, notes
)
SELECT
    a.ct2_agent_id,
    s.ct2_staff_id,
    'Primary account manager',
    'active',
    CURDATE() - INTERVAL 14 DAY,
    NULL,
    'Seeded assignment for agent-staff QA coverage.'
FROM ct2_agents AS a
INNER JOIN ct2_staff AS s ON s.staff_code = 'STF-CT2-002'
WHERE a.agent_code = 'AGT-CT2-001'
  AND NOT EXISTS (
      SELECT 1
      FROM ct2_agent_staff_assignments AS asa
      WHERE asa.ct2_agent_id = a.ct2_agent_id
        AND asa.ct2_staff_id = s.ct2_staff_id
        AND asa.assignment_role = 'Primary account manager'
  );

INSERT INTO ct2_suppliers (
    supplier_code, supplier_name, supplier_type, primary_contact_name, email, phone,
    service_category, support_tier, approval_status, onboarding_status, active_status,
    risk_level, internal_owner_user_id, external_supplier_id, source_system, created_by, updated_by
)
SELECT
    seed.supplier_code,
    seed.supplier_name,
    seed.supplier_type,
    seed.primary_contact_name,
    seed.email,
    seed.phone,
    seed.service_category,
    seed.support_tier,
    seed.approval_status,
    seed.onboarding_status,
    seed.active_status,
    seed.risk_level,
    owner.ct2_user_id,
    seed.external_supplier_id,
    seed.source_system,
    admin.ct2_user_id,
    admin.ct2_user_id
FROM (
    SELECT 'SUP-CT2-001' AS supplier_code, 'Skyline Coach Services' AS supplier_name, 'supplier' AS supplier_type, 'Mira Salonga' AS primary_contact_name, 'mira@skylinecoach.example.com' AS email, '+63-917-300-0001' AS phone, 'Transport' AS service_category, 'strategic' AS support_tier, 'approved' AS approval_status, 'live' AS onboarding_status, 'active' AS active_status, 'low' AS risk_level, 'ct2manager' AS owner_username, 'FIN-SUP-3101' AS external_supplier_id, 'financials' AS source_system
    UNION ALL SELECT 'SUP-CT2-002', 'Harborview Suites', 'partner', 'Jonas Lim', 'jonas@harborview.example.com', '+63-917-300-0002', 'Hotel', 'priority', 'pending', 'in_review', 'active', 'medium', 'ct2lead', 'FIN-SUP-3102', 'financials'
) AS seed
INNER JOIN ct2_users AS owner ON owner.username = seed.owner_username
INNER JOIN ct2_users AS admin ON admin.username = 'ct2admin'
WHERE NOT EXISTS (
    SELECT 1
    FROM ct2_suppliers AS s
    WHERE s.supplier_code = seed.supplier_code
);

INSERT INTO ct2_supplier_contacts (
    ct2_supplier_id, contact_name, role_title, email, phone, is_primary
)
SELECT s.ct2_supplier_id, seed.contact_name, seed.role_title, seed.email, seed.phone, 1
FROM (
    SELECT 'SUP-CT2-001' AS supplier_code, 'Mira Salonga' AS contact_name, 'Account Director' AS role_title, 'mira@skylinecoach.example.com' AS email, '+63-917-300-0001' AS phone
    UNION ALL SELECT 'SUP-CT2-002', 'Jonas Lim', 'Commercial Manager', 'jonas@harborview.example.com', '+63-917-300-0002'
) AS seed
INNER JOIN ct2_suppliers AS s ON s.supplier_code = seed.supplier_code
WHERE NOT EXISTS (
    SELECT 1
    FROM ct2_supplier_contacts AS c
    WHERE c.ct2_supplier_id = s.ct2_supplier_id
      AND c.email = seed.email
);

INSERT INTO ct2_supplier_onboarding (
    ct2_supplier_id, checklist_status, documents_status, compliance_status, review_notes,
    blocked_reason, target_go_live_date, completed_at, updated_by
)
SELECT
    s.ct2_supplier_id,
    seed.checklist_status,
    seed.documents_status,
    seed.compliance_status,
    seed.review_notes,
    seed.blocked_reason,
    seed.target_go_live_date,
    seed.completed_at,
    updater.ct2_user_id
FROM (
    SELECT 'SUP-CT2-001' AS supplier_code, 'completed' AS checklist_status, 'complete' AS documents_status, 'cleared' AS compliance_status, 'Supplier fully cleared for dispatch and transport allocations.' AS review_notes, NULL AS blocked_reason, CURDATE() - INTERVAL 10 DAY AS target_go_live_date, NOW() - INTERVAL 7 DAY AS completed_at, 'ct2manager' AS updater_username
    UNION ALL SELECT 'SUP-CT2-002', 'review_ready', 'partial', 'pending', 'Waiting on hotel liability certificate before go-live approval.', 'Pending liability certificate upload', CURDATE() + INTERVAL 12 DAY, NULL, 'ct2lead'
) AS seed
INNER JOIN ct2_suppliers AS s ON s.supplier_code = seed.supplier_code
INNER JOIN ct2_users AS updater ON updater.username = seed.updater_username
WHERE NOT EXISTS (
    SELECT 1
    FROM ct2_supplier_onboarding AS o
    WHERE o.ct2_supplier_id = s.ct2_supplier_id
);

INSERT INTO ct2_supplier_contracts (
    ct2_supplier_id, contract_code, contract_title, effective_date, expiry_date, renewal_status,
    contract_status, clause_summary, mock_signature_status, finance_handoff_status, created_by, updated_by
)
SELECT
    s.ct2_supplier_id,
    seed.contract_code,
    seed.contract_title,
    seed.effective_date,
    seed.expiry_date,
    seed.renewal_status,
    seed.contract_status,
    seed.clause_summary,
    seed.mock_signature_status,
    seed.finance_handoff_status,
    admin.ct2_user_id,
    admin.ct2_user_id
FROM (
    SELECT 'SUP-CT2-001' AS supplier_code, 'CTR-CT2-001' AS contract_code, 'Skyline Transport Master Services Agreement' AS contract_title, CURDATE() - INTERVAL 60 DAY AS effective_date, CURDATE() + INTERVAL 305 DAY AS expiry_date, 'not_started' AS renewal_status, 'active' AS contract_status, 'Priority support on North Luzon departures with finance-confirmed dispatch billing.' AS clause_summary, 'signed' AS mock_signature_status, 'confirmed' AS finance_handoff_status
    UNION ALL SELECT 'SUP-CT2-002', 'CTR-CT2-002', 'Harborview Seasonal Hotel Allocation', CURDATE() - INTERVAL 15 DAY, CURDATE() + INTERVAL 180 DAY, 'renewal_due', 'pending_signature', 'Room blocks reserved pending final insurance review and signature packet.', 'sent', 'shared'
) AS seed
INNER JOIN ct2_suppliers AS s ON s.supplier_code = seed.supplier_code
INNER JOIN ct2_users AS admin ON admin.username = 'ct2admin'
WHERE NOT EXISTS (
    SELECT 1
    FROM ct2_supplier_contracts AS c
    WHERE c.contract_code = seed.contract_code
);

INSERT INTO ct2_supplier_kpis (
    ct2_supplier_id, measurement_date, service_score, delivery_score, compliance_score,
    responsiveness_score, weighted_score, risk_flag, notes, created_by
)
SELECT
    s.ct2_supplier_id,
    seed.measurement_date,
    seed.service_score,
    seed.delivery_score,
    seed.compliance_score,
    seed.responsiveness_score,
    seed.weighted_score,
    seed.risk_flag,
    seed.notes,
    creator.ct2_user_id
FROM (
    SELECT 'SUP-CT2-001' AS supplier_code, CURDATE() - INTERVAL 5 DAY AS measurement_date, 94.00 AS service_score, 91.00 AS delivery_score, 96.00 AS compliance_score, 92.00 AS responsiveness_score, 93.25 AS weighted_score, 'none' AS risk_flag, 'Consistent performance for current dispatch volume.' AS notes, 'ct2lead' AS creator_username
    UNION ALL SELECT 'SUP-CT2-002', CURDATE() - INTERVAL 3 DAY, 81.00, 76.00, 72.00, 79.00, 77.00, 'watch', 'Follow-up required on incomplete onboarding package.', 'ct2lead'
) AS seed
INNER JOIN ct2_suppliers AS s ON s.supplier_code = seed.supplier_code
INNER JOIN ct2_users AS creator ON creator.username = seed.creator_username
WHERE NOT EXISTS (
    SELECT 1
    FROM ct2_supplier_kpis AS k
    WHERE k.ct2_supplier_id = s.ct2_supplier_id
      AND k.notes = seed.notes
);

INSERT INTO ct2_supplier_relationship_notes (
    ct2_supplier_id, note_type, note_title, note_body, next_action_date, created_by
)
SELECT
    s.ct2_supplier_id,
    seed.note_type,
    seed.note_title,
    seed.note_body,
    seed.next_action_date,
    creator.ct2_user_id
FROM (
    SELECT 'SUP-CT2-001' AS supplier_code, 'review' AS note_type, 'Quarterly dispatch review' AS note_title, 'Skyline coach utilization remains stable with no outstanding transport complaints.' AS note_body, CURDATE() + INTERVAL 30 DAY AS next_action_date, 'ct2manager' AS creator_username
    UNION ALL SELECT 'SUP-CT2-002', 'escalation', 'Insurance document follow-up', 'Hotel onboarding remains in review until updated liability coverage is attached in CT2.', CURDATE() + INTERVAL 5 DAY, 'ct2lead'
) AS seed
INNER JOIN ct2_suppliers AS s ON s.supplier_code = seed.supplier_code
INNER JOIN ct2_users AS creator ON creator.username = seed.creator_username
WHERE NOT EXISTS (
    SELECT 1
    FROM ct2_supplier_relationship_notes AS n
    WHERE n.ct2_supplier_id = s.ct2_supplier_id
      AND n.note_title = seed.note_title
);

INSERT INTO ct2_tour_packages (
    package_name, base_price, margin_percentage, is_active, created_by, updated_by
)
SELECT
    seed.package_name,
    seed.base_price,
    seed.margin_percentage,
    seed.is_active,
    admin.ct2_user_id,
    admin.ct2_user_id
FROM (
    SELECT 'Northern Luzon Discovery QA' AS package_name, 18500.00 AS base_price, 18.00 AS margin_percentage, 1 AS is_active
    UNION ALL SELECT 'Cebu Harbor Escape QA', 14650.00, 16.00, 1
) AS seed
INNER JOIN ct2_users AS admin ON admin.username = 'ct2admin'
WHERE NOT EXISTS (
    SELECT 1
    FROM ct2_tour_packages AS p
    WHERE p.package_name = seed.package_name
);

INSERT INTO ct2_inventory_resources (
    ct2_supplier_id, resource_name, resource_type, capacity, base_cost, status, notes, created_by, updated_by
)
SELECT
    s.ct2_supplier_id,
    seed.resource_name,
    seed.resource_type,
    seed.capacity,
    seed.base_cost,
    seed.status,
    seed.notes,
    admin.ct2_user_id,
    admin.ct2_user_id
FROM (
    SELECT 'SUP-CT2-001' AS supplier_code, 'Skyline Coaster 18-Seater' AS resource_name, 'transport' AS resource_type, 18 AS capacity, 9200.00 AS base_cost, 'available' AS status, 'Primary coach for North Luzon departures.' AS notes
    UNION ALL SELECT 'SUP-CT2-002', 'Harborview Deluxe Room Block', 'hotel', 12, 4800.00, 'available', 'Seeded room inventory for supplier allocation QA.'
) AS seed
INNER JOIN ct2_suppliers AS s ON s.supplier_code = seed.supplier_code
INNER JOIN ct2_users AS admin ON admin.username = 'ct2admin'
WHERE NOT EXISTS (
    SELECT 1
    FROM ct2_inventory_resources AS r
    WHERE r.resource_name = seed.resource_name
      AND r.ct2_supplier_id = s.ct2_supplier_id
);

INSERT INTO ct2_package_resources (ct2_package_id, ct2_resource_id, units_required)
SELECT p.ct2_package_id, r.ct2_resource_id, seed.units_required
FROM (
    SELECT 'Northern Luzon Discovery QA' AS package_name, 'Skyline Coaster 18-Seater' AS resource_name, 1 AS units_required
    UNION ALL SELECT 'Cebu Harbor Escape QA', 'Harborview Deluxe Room Block', 8
) AS seed
INNER JOIN ct2_tour_packages AS p ON p.package_name = seed.package_name
INNER JOIN ct2_inventory_resources AS r ON r.resource_name = seed.resource_name
WHERE NOT EXISTS (
    SELECT 1
    FROM ct2_package_resources AS pr
    WHERE pr.ct2_package_id = p.ct2_package_id
      AND pr.ct2_resource_id = r.ct2_resource_id
);

INSERT INTO ct2_resource_allocations (
    ct2_resource_id, ct2_package_id, external_booking_id, allocation_date, pax_count,
    reserved_units, allocation_status, notes, created_by
)
SELECT
    r.ct2_resource_id,
    p.ct2_package_id,
    seed.external_booking_id,
    seed.allocation_date,
    seed.pax_count,
    seed.reserved_units,
    seed.allocation_status,
    seed.notes,
    creator.ct2_user_id
FROM (
    SELECT 'Skyline Coaster 18-Seater' AS resource_name, 'Northern Luzon Discovery QA' AS package_name, 'CT1-BKG-1001' AS external_booking_id, CURDATE() + INTERVAL 2 DAY AS allocation_date, 14 AS pax_count, 1 AS reserved_units, 'reserved' AS allocation_status, 'Seeded booking allocation for dispatch QA.' AS notes, 'ct2lead' AS creator_username
    UNION ALL SELECT 'Harborview Deluxe Room Block', 'Cebu Harbor Escape QA', 'CT1-BKG-1003', CURDATE() + INTERVAL 9 DAY, 8, 8, 'soft_blocked', 'Seeded block to validate conflict visibility.', 'ct2lead'
) AS seed
INNER JOIN ct2_inventory_resources AS r ON r.resource_name = seed.resource_name
INNER JOIN ct2_tour_packages AS p ON p.package_name = seed.package_name
INNER JOIN ct2_users AS creator ON creator.username = seed.creator_username
WHERE NOT EXISTS (
    SELECT 1
    FROM ct2_resource_allocations AS ra
    WHERE ra.external_booking_id = seed.external_booking_id
);

INSERT INTO ct2_seasonal_blocks (
    ct2_resource_id, start_date, end_date, reason, block_type, created_by
)
SELECT
    r.ct2_resource_id,
    CURDATE() + INTERVAL 20 DAY,
    CURDATE() + INTERVAL 24 DAY,
    'Planned fleet servicing window',
    'maintenance',
    creator.ct2_user_id
FROM ct2_inventory_resources AS r
INNER JOIN ct2_users AS creator ON creator.username = 'ct2lead'
WHERE r.resource_name = 'Skyline Coaster 18-Seater'
  AND NOT EXISTS (
      SELECT 1
      FROM ct2_seasonal_blocks AS b
      WHERE b.ct2_resource_id = r.ct2_resource_id
        AND b.reason = 'Planned fleet servicing window'
  );

INSERT INTO ct2_dispatch_vehicles (plate_number, model_name, capacity, current_mileage, status)
SELECT seed.plate_number, seed.model_name, seed.capacity, seed.current_mileage, seed.status
FROM (
    SELECT 'NAA-4581' AS plate_number, 'Toyota Coaster QA Unit' AS model_name, 18 AS capacity, 45810 AS current_mileage, 'available' AS status
    UNION ALL SELECT 'NBA-9024', 'Ford Transit Support Van', 12, 28940, 'maintenance'
) AS seed
WHERE NOT EXISTS (
    SELECT 1
    FROM ct2_dispatch_vehicles AS v
    WHERE v.plate_number = seed.plate_number
);

INSERT INTO ct2_dispatch_drivers (full_name, license_expiry, status)
SELECT seed.full_name, seed.license_expiry, seed.status
FROM (
    SELECT 'Aris Navarro' AS full_name, CURDATE() + INTERVAL 420 DAY AS license_expiry, 'available' AS status
    UNION ALL SELECT 'Jude Mariano', CURDATE() + INTERVAL 300 DAY, 'assigned'
) AS seed
WHERE NOT EXISTS (
    SELECT 1
    FROM ct2_dispatch_drivers AS d
    WHERE d.full_name = seed.full_name
);

INSERT INTO ct2_dispatch_orders (
    ct2_allocation_id, ct2_vehicle_id, ct2_driver_id, dispatch_date, dispatch_time,
    return_time, start_mileage, end_mileage, dispatch_status, created_by
)
SELECT
    a.ct2_allocation_id,
    v.ct2_vehicle_id,
    d.ct2_driver_id,
    CURDATE() + INTERVAL 2 DAY,
    DATE_ADD(TIMESTAMP(CURDATE() + INTERVAL 2 DAY, '06:30:00'), INTERVAL 0 SECOND),
    DATE_ADD(TIMESTAMP(CURDATE() + INTERVAL 2 DAY, '21:15:00'), INTERVAL 0 SECOND),
    45810,
    46180,
    'scheduled',
    creator.ct2_user_id
FROM ct2_resource_allocations AS a
INNER JOIN ct2_dispatch_vehicles AS v ON v.plate_number = 'NAA-4581'
INNER JOIN ct2_dispatch_drivers AS d ON d.full_name = 'Aris Navarro'
INNER JOIN ct2_users AS creator ON creator.username = 'ct2lead'
WHERE a.external_booking_id = 'CT1-BKG-1001'
  AND NOT EXISTS (
      SELECT 1
      FROM ct2_dispatch_orders AS o
      WHERE o.ct2_allocation_id = a.ct2_allocation_id
        AND o.ct2_vehicle_id = v.ct2_vehicle_id
  );

INSERT INTO ct2_maintenance_logs (
    ct2_vehicle_id, service_date, service_type, mechanic_notes, cost, created_by
)
SELECT
    v.ct2_vehicle_id,
    CURDATE() - INTERVAL 6 DAY,
    'Preventive maintenance',
    'Brake line inspection and oil change completed for QA maintenance history.',
    6400.00,
    creator.ct2_user_id
FROM ct2_dispatch_vehicles AS v
INNER JOIN ct2_users AS creator ON creator.username = 'ct2lead'
WHERE v.plate_number = 'NBA-9024'
  AND NOT EXISTS (
      SELECT 1
      FROM ct2_maintenance_logs AS ml
      WHERE ml.ct2_vehicle_id = v.ct2_vehicle_id
        AND ml.service_type = 'Preventive maintenance'
  );

INSERT INTO ct2_campaigns (
    campaign_code, campaign_name, campaign_type, channel_type, start_date, end_date,
    budget_amount, status, approval_status, target_audience, external_customer_segment_id,
    source_system, created_by, updated_by
)
SELECT
    seed.campaign_code,
    seed.campaign_name,
    seed.campaign_type,
    seed.channel_type,
    seed.start_date,
    seed.end_date,
    seed.budget_amount,
    seed.status,
    seed.approval_status,
    seed.target_audience,
    seed.external_customer_segment_id,
    seed.source_system,
    creator.ct2_user_id,
    creator.ct2_user_id
FROM (
    SELECT 'CT2-MKT-001' AS campaign_code, 'North Luzon Coach Summer Push' AS campaign_name, 'seasonal' AS campaign_type, 'hybrid' AS channel_type, CURDATE() - INTERVAL 5 DAY AS start_date, CURDATE() + INTERVAL 25 DAY AS end_date, 150000.00 AS budget_amount, 'active' AS status, 'approved' AS approval_status, 'Group travelers and agency partners' AS target_audience, 'SEG-NORTH-01' AS external_customer_segment_id, 'crm' AS source_system, 'ct2manager' AS creator_username
    UNION ALL SELECT 'CT2-MKT-002', 'Harborview Weekend Escape', 'partner', 'affiliate', CURDATE(), CURDATE() + INTERVAL 21 DAY, 90000.00, 'pending_approval', 'pending', 'Short-stay coastal travelers', 'SEG-COAST-02', 'crm', 'ct2lead'
) AS seed
INNER JOIN ct2_users AS creator ON creator.username = seed.creator_username
WHERE NOT EXISTS (
    SELECT 1
    FROM ct2_campaigns AS c
    WHERE c.campaign_code = seed.campaign_code
);

INSERT INTO ct2_promotions (
    ct2_campaign_id, promotion_code, promotion_name, promotion_type, discount_value,
    eligibility_rule, valid_from, valid_until, usage_limit, promotion_status, approval_status,
    external_booking_scope, source_system, created_by, updated_by
)
SELECT
    c.ct2_campaign_id,
    seed.promotion_code,
    seed.promotion_name,
    seed.promotion_type,
    seed.discount_value,
    seed.eligibility_rule,
    seed.valid_from,
    seed.valid_until,
    seed.usage_limit,
    seed.promotion_status,
    seed.approval_status,
    seed.external_booking_scope,
    seed.source_system,
    creator.ct2_user_id,
    creator.ct2_user_id
FROM (
    SELECT 'CT2-MKT-001' AS campaign_code, 'PROMO-CT2-001' AS promotion_code, 'North Luzon Early Bird' AS promotion_name, 'percentage' AS promotion_type, 12.50 AS discount_value, 'Applies to bookings created at least 14 days before departure.' AS eligibility_rule, CURDATE() - INTERVAL 5 DAY AS valid_from, CURDATE() + INTERVAL 20 DAY AS valid_until, 150 AS usage_limit, 'active' AS promotion_status, 'approved' AS approval_status, 'north_luzon_departures' AS external_booking_scope, 'ct1' AS source_system, 'ct2manager' AS creator_username
    UNION ALL SELECT 'CT2-MKT-002', 'PROMO-CT2-002', 'Harborview Flash Partner Rate', 'fixed_amount', 1500.00, 'Affiliate-only hotel package incentive pending final approval.', CURDATE(), CURDATE() + INTERVAL 10 DAY, 50, 'pending_approval', 'pending', 'cebu_weekend_packages', 'ct1', 'ct2lead'
) AS seed
INNER JOIN ct2_campaigns AS c ON c.campaign_code = seed.campaign_code
INNER JOIN ct2_users AS creator ON creator.username = seed.creator_username
WHERE NOT EXISTS (
    SELECT 1
    FROM ct2_promotions AS p
    WHERE p.promotion_code = seed.promotion_code
);

INSERT INTO ct2_vouchers (
    ct2_promotion_id, voucher_code, voucher_name, customer_scope, max_redemptions,
    redeemed_count, voucher_status, valid_from, valid_until, external_customer_id,
    source_system, created_by
)
SELECT
    p.ct2_promotion_id,
    seed.voucher_code,
    seed.voucher_name,
    seed.customer_scope,
    seed.max_redemptions,
    seed.redeemed_count,
    seed.voucher_status,
    seed.valid_from,
    seed.valid_until,
    seed.external_customer_id,
    seed.source_system,
    creator.ct2_user_id
FROM (
    SELECT 'PROMO-CT2-001' AS promotion_code, 'VOUCH-CT2-001' AS voucher_code, 'North Luzon VIP Voucher' AS voucher_name, 'single_use' AS customer_scope, 1 AS max_redemptions, 0 AS redeemed_count, 'active' AS voucher_status, CURDATE() - INTERVAL 2 DAY AS valid_from, CURDATE() + INTERVAL 12 DAY AS valid_until, 'CT1-CUST-8801' AS external_customer_id, 'ct1' AS source_system, 'ct2manager' AS creator_username
    UNION ALL SELECT 'PROMO-CT2-002', 'VOUCH-CT2-002', 'Harborview Affiliate Voucher', 'affiliate', 20, 0, 'issued', CURDATE(), CURDATE() + INTERVAL 10 DAY, NULL, 'ct1', 'ct2lead'
) AS seed
INNER JOIN ct2_promotions AS p ON p.promotion_code = seed.promotion_code
INNER JOIN ct2_users AS creator ON creator.username = seed.creator_username
WHERE NOT EXISTS (
    SELECT 1
    FROM ct2_vouchers AS v
    WHERE v.voucher_code = seed.voucher_code
);

INSERT INTO ct2_affiliates (
    affiliate_code, affiliate_name, contact_name, email, phone, affiliate_status,
    commission_rate, payout_status, referral_code, external_partner_id, source_system, created_by, updated_by
)
SELECT
    seed.affiliate_code,
    seed.affiliate_name,
    seed.contact_name,
    seed.email,
    seed.phone,
    seed.affiliate_status,
    seed.commission_rate,
    seed.payout_status,
    seed.referral_code,
    seed.external_partner_id,
    seed.source_system,
    creator.ct2_user_id,
    creator.ct2_user_id
FROM (
    SELECT 'AFF-CT2-001' AS affiliate_code, 'Biyahe Deals Network' AS affiliate_name, 'Cris Villanueva' AS contact_name, 'cris@biyahenetwork.example.com' AS email, '+63-917-400-0001' AS phone, 'active' AS affiliate_status, 8.50 AS commission_rate, 'ready' AS payout_status, 'BIYAHE-QA' AS referral_code, 'PARTNER-7701' AS external_partner_id, 'partner_portal' AS source_system, 'ct2manager' AS creator_username
    UNION ALL SELECT 'AFF-CT2-002', 'Weekend Escape Collective', 'Mina Ocampo', 'mina@weekendescape.example.com', '+63-917-400-0002', 'onboarding', 6.00, 'pending_setup', 'WESCAPE-QA', 'PARTNER-7702', 'partner_portal', 'ct2lead'
) AS seed
INNER JOIN ct2_users AS creator ON creator.username = seed.creator_username
WHERE NOT EXISTS (
    SELECT 1
    FROM ct2_affiliates AS a
    WHERE a.affiliate_code = seed.affiliate_code
);

INSERT INTO ct2_referral_clicks (
    ct2_affiliate_id, ct2_campaign_id, referral_code, click_date, landing_page,
    external_customer_id, external_booking_id, attribution_status, source_system, created_by
)
SELECT
    a.ct2_affiliate_id,
    c.ct2_campaign_id,
    seed.referral_code,
    seed.click_date,
    seed.landing_page,
    seed.external_customer_id,
    seed.external_booking_id,
    seed.attribution_status,
    seed.source_system,
    creator.ct2_user_id
FROM (
    SELECT 'AFF-CT2-001' AS affiliate_code, 'CT2-MKT-001' AS campaign_code, 'BIYAHE-QA' AS referral_code, NOW() - INTERVAL 3 DAY AS click_date, '/promos/north-luzon' AS landing_page, 'CT1-CUST-8801' AS external_customer_id, 'CT1-BKG-1001' AS external_booking_id, 'booked' AS attribution_status, 'web' AS source_system, 'ct2manager' AS creator_username
    UNION ALL SELECT 'AFF-CT2-002', 'CT2-MKT-002', 'WESCAPE-QA', NOW() - INTERVAL 1 DAY, '/promos/harborview', 'CT1-CUST-8803', NULL, 'qualified', 'web', 'ct2lead'
) AS seed
INNER JOIN ct2_affiliates AS a ON a.affiliate_code = seed.affiliate_code
INNER JOIN ct2_campaigns AS c ON c.campaign_code = seed.campaign_code
INNER JOIN ct2_users AS creator ON creator.username = seed.creator_username
WHERE NOT EXISTS (
    SELECT 1
    FROM ct2_referral_clicks AS rc
    WHERE rc.ct2_affiliate_id = a.ct2_affiliate_id
      AND rc.referral_code = seed.referral_code
      AND rc.external_customer_id = seed.external_customer_id
);

INSERT INTO ct2_redemption_logs (
    ct2_campaign_id, ct2_promotion_id, ct2_voucher_id, redemption_date, external_customer_id,
    external_booking_id, redeemed_amount, redemption_status, source_system, created_by
)
SELECT
    c.ct2_campaign_id,
    p.ct2_promotion_id,
    v.ct2_voucher_id,
    seed.redemption_date,
    seed.external_customer_id,
    seed.external_booking_id,
    seed.redeemed_amount,
    seed.redemption_status,
    seed.source_system,
    creator.ct2_user_id
FROM (
    SELECT 'CT2-MKT-001' AS campaign_code, 'PROMO-CT2-001' AS promotion_code, 'VOUCH-CT2-001' AS voucher_code, NOW() - INTERVAL 2 DAY AS redemption_date, 'CT1-CUST-8801' AS external_customer_id, 'CT1-BKG-1001' AS external_booking_id, 2200.00 AS redeemed_amount, 'redeemed' AS redemption_status, 'ct1' AS source_system, 'ct2manager' AS creator_username
) AS seed
INNER JOIN ct2_campaigns AS c ON c.campaign_code = seed.campaign_code
INNER JOIN ct2_promotions AS p ON p.promotion_code = seed.promotion_code
INNER JOIN ct2_vouchers AS v ON v.voucher_code = seed.voucher_code
INNER JOIN ct2_users AS creator ON creator.username = seed.creator_username
WHERE NOT EXISTS (
    SELECT 1
    FROM ct2_redemption_logs AS rl
    WHERE rl.ct2_voucher_id = v.ct2_voucher_id
      AND rl.external_booking_id = seed.external_booking_id
);

INSERT INTO ct2_campaign_metrics (
    ct2_campaign_id, report_date, impressions_count, click_count, lead_count,
    conversion_count, attributed_revenue, positive_reviews, neutral_reviews,
    negative_reviews, external_review_batch_id, source_system, created_by
)
SELECT
    c.ct2_campaign_id,
    seed.report_date,
    seed.impressions_count,
    seed.click_count,
    seed.lead_count,
    seed.conversion_count,
    seed.attributed_revenue,
    seed.positive_reviews,
    seed.neutral_reviews,
    seed.negative_reviews,
    seed.external_review_batch_id,
    seed.source_system,
    creator.ct2_user_id
FROM (
    SELECT 'CT2-MKT-001' AS campaign_code, CURDATE() - INTERVAL 1 DAY AS report_date, 18200 AS impressions_count, 940 AS click_count, 148 AS lead_count, 39 AS conversion_count, 356000.00 AS attributed_revenue, 24 AS positive_reviews, 5 AS neutral_reviews, 1 AS negative_reviews, 'REV-BATCH-1101' AS external_review_batch_id, 'analytics' AS source_system, 'ct2manager' AS creator_username
    UNION ALL SELECT 'CT2-MKT-002', CURDATE(), 8700, 320, 61, 11, 98000.00, 9, 3, 1, 'REV-BATCH-1102', 'analytics', 'ct2lead'
) AS seed
INNER JOIN ct2_campaigns AS c ON c.campaign_code = seed.campaign_code
INNER JOIN ct2_users AS creator ON creator.username = seed.creator_username
WHERE NOT EXISTS (
    SELECT 1
    FROM ct2_campaign_metrics AS cm
    WHERE cm.ct2_campaign_id = c.ct2_campaign_id
      AND cm.external_review_batch_id = seed.external_review_batch_id
);

INSERT INTO ct2_marketing_notes (
    ct2_campaign_id, ct2_affiliate_id, note_type, note_title, note_body, next_action_date, created_by
)
SELECT
    c.ct2_campaign_id,
    a.ct2_affiliate_id,
    seed.note_type,
    seed.note_title,
    seed.note_body,
    seed.next_action_date,
    creator.ct2_user_id
FROM (
    SELECT 'CT2-MKT-001' AS campaign_code, 'AFF-CT2-001' AS affiliate_code, 'performance' AS note_type, 'Agency channel outperformed target' AS note_title, 'North Luzon campaign is exceeding baseline attributed revenue after first affiliate push.' AS note_body, CURDATE() + INTERVAL 7 DAY AS next_action_date, 'ct2manager' AS creator_username
    UNION ALL SELECT 'CT2-MKT-002', 'AFF-CT2-002', 'partner_follow_up', 'Pending payout setup review', 'Affiliate onboarding must be completed before Harborview campaign can be fully activated.', CURDATE() + INTERVAL 3 DAY, 'ct2lead'
) AS seed
INNER JOIN ct2_campaigns AS c ON c.campaign_code = seed.campaign_code
INNER JOIN ct2_affiliates AS a ON a.affiliate_code = seed.affiliate_code
INNER JOIN ct2_users AS creator ON creator.username = seed.creator_username
WHERE NOT EXISTS (
    SELECT 1
    FROM ct2_marketing_notes AS mn
    WHERE mn.note_title = seed.note_title
);

INSERT INTO ct2_visa_types (
    visa_code, country_name, visa_category, processing_days, biometrics_required,
    validity_period_days, base_fee, is_active
)
SELECT seed.visa_code, seed.country_name, seed.visa_category, seed.processing_days, seed.biometrics_required, seed.validity_period_days, seed.base_fee, seed.is_active
FROM (
    SELECT 'VISA-SG-TOUR' AS visa_code, 'Singapore' AS country_name, 'Tourist' AS visa_category, 7 AS processing_days, 0 AS biometrics_required, 30 AS validity_period_days, 3500.00 AS base_fee, 1 AS is_active
    UNION ALL SELECT 'VISA-JP-BIZ', 'Japan', 'Business', 12, 1, 90, 6200.00, 1
) AS seed
WHERE NOT EXISTS (
    SELECT 1
    FROM ct2_visa_types AS vt
    WHERE vt.visa_code = seed.visa_code
);

INSERT INTO ct2_visa_applications (
    ct2_visa_type_id, application_reference, external_customer_id, external_agent_id,
    source_system, status, submission_date, appointment_date, embassy_reference,
    approval_status, documents_verified, outstanding_item_count, payment_status, remarks,
    created_by, updated_by
)
SELECT
    vt.ct2_visa_type_id,
    seed.application_reference,
    seed.external_customer_id,
    seed.external_agent_id,
    seed.source_system,
    seed.status,
    seed.submission_date,
    seed.appointment_date,
    seed.embassy_reference,
    seed.approval_status,
    seed.documents_verified,
    seed.outstanding_item_count,
    seed.payment_status,
    seed.remarks,
    creator.ct2_user_id,
    creator.ct2_user_id
FROM (
    SELECT 'VISA-SG-TOUR' AS visa_code, 'VISA-APP-001' AS application_reference, 'CT1-CUST-8801' AS external_customer_id, 'AGT-CT2-001' AS external_agent_id, 'ct1' AS source_system, 'document_review' AS status, CURDATE() - INTERVAL 4 DAY AS submission_date, DATE_ADD(TIMESTAMP(CURDATE() + INTERVAL 4 DAY, '09:00:00'), INTERVAL 0 SECOND) AS appointment_date, 'EMB-SG-1001' AS embassy_reference, 'not_required' AS approval_status, 0 AS documents_verified, 2 AS outstanding_item_count, 'partial' AS payment_status, 'Passport copy uploaded; financial proof still pending.' AS remarks, 'ct2desk' AS creator_username
    UNION ALL SELECT 'VISA-JP-BIZ', 'VISA-APP-002', 'CT1-CUST-8804', 'AGT-CT2-002', 'partner_portal', 'escalated_review', CURDATE() - INTERVAL 1 DAY, DATE_ADD(TIMESTAMP(CURDATE() + INTERVAL 6 DAY, '13:30:00'), INTERVAL 0 SECOND), 'EMB-JP-2044', 'pending', 0, 3, 'unpaid', 'Requires management review due to missing employer certificate.', 'ct2desk'
) AS seed
INNER JOIN ct2_visa_types AS vt ON vt.visa_code = seed.visa_code
INNER JOIN ct2_users AS creator ON creator.username = seed.creator_username
WHERE NOT EXISTS (
    SELECT 1
    FROM ct2_visa_applications AS va
    WHERE va.application_reference = seed.application_reference
);

INSERT INTO ct2_visa_checklist_items (
    ct2_visa_type_id, item_name, item_description, is_mandatory,
    file_size_limit_mb, requires_original, display_order
)
SELECT
    vt.ct2_visa_type_id,
    seed.item_name,
    seed.item_description,
    seed.is_mandatory,
    seed.file_size_limit_mb,
    seed.requires_original,
    seed.display_order
FROM (
    SELECT 'VISA-SG-TOUR' AS visa_code, 'Passport bio page' AS item_name, 'Valid passport with at least six months before expiry.' AS item_description, 1 AS is_mandatory, 5 AS file_size_limit_mb, 0 AS requires_original, 1 AS display_order
    UNION ALL SELECT 'VISA-SG-TOUR', 'Proof of accommodation', 'Hotel confirmation for the trip window.', 1, 5, 0, 2
    UNION ALL SELECT 'VISA-JP-BIZ', 'Company endorsement letter', 'Employer-issued business travel endorsement.', 1, 5, 1, 1
    UNION ALL SELECT 'VISA-JP-BIZ', 'Latest bank certificate', 'Proof of funds issued within 30 days.', 1, 5, 0, 2
) AS seed
INNER JOIN ct2_visa_types AS vt ON vt.visa_code = seed.visa_code
WHERE NOT EXISTS (
    SELECT 1
    FROM ct2_visa_checklist_items AS ci
    WHERE ci.ct2_visa_type_id = vt.ct2_visa_type_id
      AND ci.item_name = seed.item_name
);

INSERT INTO ct2_application_checklist (
    ct2_visa_application_id, ct2_visa_checklist_item_id, checklist_status,
    verification_notes, ct2_document_id, verified_by, verified_at
)
SELECT
    va.ct2_visa_application_id,
    ci.ct2_visa_checklist_item_id,
    CASE
        WHEN seed.item_name = 'Passport bio page' THEN 'submitted'
        WHEN seed.item_name = 'Proof of accommodation' THEN 'pending'
        WHEN seed.item_name = 'Company endorsement letter' THEN 'rejected'
        ELSE 'pending'
    END,
    CASE
        WHEN seed.item_name = 'Passport bio page' THEN 'Initial passport upload reviewed; awaiting final verification during UAT.'
        WHEN seed.item_name = 'Company endorsement letter' THEN 'Document rejected in seeded data to support exception-handling QA.'
        ELSE NULL
    END,
    NULL,
    verifier.ct2_user_id,
    CASE
        WHEN seed.item_name IN ('Passport bio page', 'Company endorsement letter') THEN NOW() - INTERVAL 1 DAY
        ELSE NULL
    END
FROM (
    SELECT 'VISA-APP-001' AS application_reference, 'Passport bio page' AS item_name, 'ct2manager' AS verifier_username
    UNION ALL SELECT 'VISA-APP-001', 'Proof of accommodation', 'ct2manager'
    UNION ALL SELECT 'VISA-APP-002', 'Company endorsement letter', 'ct2manager'
    UNION ALL SELECT 'VISA-APP-002', 'Latest bank certificate', 'ct2manager'
) AS seed
INNER JOIN ct2_visa_applications AS va ON va.application_reference = seed.application_reference
INNER JOIN ct2_visa_checklist_items AS ci ON ci.ct2_visa_type_id = va.ct2_visa_type_id AND ci.item_name = seed.item_name
INNER JOIN ct2_users AS verifier ON verifier.username = seed.verifier_username
WHERE NOT EXISTS (
    SELECT 1
    FROM ct2_application_checklist AS ac
    WHERE ac.ct2_visa_application_id = va.ct2_visa_application_id
      AND ac.ct2_visa_checklist_item_id = ci.ct2_visa_checklist_item_id
);

INSERT INTO ct2_visa_payments (
    ct2_visa_application_id, payment_reference, external_payment_id, amount, currency,
    payment_method, payment_status, paid_at, source_system, created_by
)
SELECT
    va.ct2_visa_application_id,
    seed.payment_reference,
    seed.external_payment_id,
    seed.amount,
    seed.currency,
    seed.payment_method,
    seed.payment_status,
    seed.paid_at,
    seed.source_system,
    creator.ct2_user_id
FROM (
    SELECT 'VISA-APP-001' AS application_reference, 'PAY-VISA-001' AS payment_reference, 'FIN-PAY-6601' AS external_payment_id, 3500.00 AS amount, 'PHP' AS currency, 'Manual' AS payment_method, 'completed' AS payment_status, NOW() - INTERVAL 3 DAY AS paid_at, 'cashier' AS source_system, 'ct2desk' AS creator_username
    UNION ALL SELECT 'VISA-APP-002', 'PAY-VISA-002', 'FIN-PAY-6602', 6200.00, 'PHP', 'Manual', 'pending', NULL, 'cashier', 'ct2desk'
) AS seed
INNER JOIN ct2_visa_applications AS va ON va.application_reference = seed.application_reference
INNER JOIN ct2_users AS creator ON creator.username = seed.creator_username
WHERE NOT EXISTS (
    SELECT 1
    FROM ct2_visa_payments AS vp
    WHERE vp.payment_reference = seed.payment_reference
);

INSERT INTO ct2_notification_logs (
    ct2_visa_application_id, notification_channel, recipient_reference,
    notification_subject, notification_message, delivery_status, sent_at, created_by
)
SELECT
    va.ct2_visa_application_id,
    seed.notification_channel,
    seed.recipient_reference,
    seed.notification_subject,
    seed.notification_message,
    seed.delivery_status,
    seed.sent_at,
    creator.ct2_user_id
FROM (
    SELECT 'VISA-APP-001' AS application_reference, 'email' AS notification_channel, 'traveler1@example.com' AS recipient_reference, 'Additional visa documents required' AS notification_subject, 'Please upload proof of accommodation before your scheduled appointment.' AS notification_message, 'sent' AS delivery_status, NOW() - INTERVAL 2 DAY AS sent_at, 'ct2desk' AS creator_username
    UNION ALL SELECT 'VISA-APP-002', 'portal', 'CT1-CUST-8804', 'Visa case escalated for review', 'Your visa application is under escalated review pending supporting documents.', 'queued', NULL, 'ct2desk'
) AS seed
INNER JOIN ct2_visa_applications AS va ON va.application_reference = seed.application_reference
INNER JOIN ct2_users AS creator ON creator.username = seed.creator_username
WHERE NOT EXISTS (
    SELECT 1
    FROM ct2_notification_logs AS nl
    WHERE nl.ct2_visa_application_id = va.ct2_visa_application_id
      AND nl.notification_subject = seed.notification_subject
);

INSERT INTO ct2_visa_notes (
    ct2_visa_application_id, note_type, note_body, next_action_date, created_by
)
SELECT
    va.ct2_visa_application_id,
    seed.note_type,
    seed.note_body,
    seed.next_action_date,
    creator.ct2_user_id
FROM (
    SELECT 'VISA-APP-001' AS application_reference, 'review' AS note_type, 'Accommodation confirmation is still outstanding ahead of the embassy visit.' AS note_body, CURDATE() + INTERVAL 2 DAY AS next_action_date, 'ct2manager' AS creator_username
    UNION ALL SELECT 'VISA-APP-002', 'risk', 'Escalated review is waiting for employer endorsement and payment clearance.', CURDATE() + INTERVAL 1 DAY, 'ct2manager'
) AS seed
INNER JOIN ct2_visa_applications AS va ON va.application_reference = seed.application_reference
INNER JOIN ct2_users AS creator ON creator.username = seed.creator_username
WHERE NOT EXISTS (
    SELECT 1
    FROM ct2_visa_notes AS vn
    WHERE vn.ct2_visa_application_id = va.ct2_visa_application_id
      AND vn.note_body = seed.note_body
);

INSERT INTO ct2_approval_workflows (
    subject_type, subject_id, requested_by, approver_user_id, approval_status, requested_at, decided_at, decision_notes
)
SELECT
    seed.subject_type,
    seed.subject_id,
    requester.ct2_user_id,
    NULL,
    'pending',
    NOW() - INTERVAL seed.hours_ago HOUR,
    NULL,
    seed.decision_notes
FROM (
    SELECT 'supplier' AS subject_type, s.ct2_supplier_id AS subject_id, 'ct2lead' AS requester_username, 48 AS hours_ago, 'Seeded pending supplier approval for QA.' AS decision_notes
    FROM ct2_suppliers AS s
    WHERE s.supplier_code = 'SUP-CT2-002'
    UNION ALL
    SELECT 'campaign', c.ct2_campaign_id, 'ct2lead', 36, 'Seeded pending campaign approval for QA.'
    FROM ct2_campaigns AS c
    WHERE c.campaign_code = 'CT2-MKT-002'
    UNION ALL
    SELECT 'promotion', p.ct2_promotion_id, 'ct2lead', 30, 'Seeded pending promotion approval for QA.'
    FROM ct2_promotions AS p
    WHERE p.promotion_code = 'PROMO-CT2-002'
    UNION ALL
    SELECT 'visa_application', va.ct2_visa_application_id, 'ct2desk', 18, 'Seeded visa exception approval for QA.'
    FROM ct2_visa_applications AS va
    WHERE va.application_reference = 'VISA-APP-002'
) AS seed
INNER JOIN ct2_users AS requester ON requester.username = seed.requester_username
WHERE NOT EXISTS (
    SELECT 1
    FROM ct2_approval_workflows AS aw
    WHERE aw.subject_type = seed.subject_type
      AND aw.subject_id = seed.subject_id
);

INSERT INTO ct2_report_runs (
    ct2_financial_report_id, run_label, date_from, date_to, module_key, source_system, generated_by
)
SELECT
    fr.ct2_financial_report_id,
    'QA Baseline Cross-Module Run',
    CURDATE() - INTERVAL 30 DAY,
    CURDATE(),
    'all',
    'ct2_seed',
    generator.ct2_user_id
FROM ct2_financial_reports AS fr
INNER JOIN ct2_users AS generator ON generator.username = 'ct2finance'
WHERE fr.report_code = 'CT2-OPS-001'
  AND NOT EXISTS (
      SELECT 1
      FROM ct2_report_runs AS rr
      WHERE rr.run_label = 'QA Baseline Cross-Module Run'
        AND rr.ct2_financial_report_id = fr.ct2_financial_report_id
  );

INSERT INTO ct2_financial_snapshots (
    ct2_report_run_id, snapshot_type, reference_code, source_module, source_record_id,
    metric_label, metric_value, metric_count, status_flag, external_reference_id, notes
)
SELECT
    rr.ct2_report_run_id,
    seed.snapshot_type,
    seed.reference_code,
    seed.source_module,
    seed.source_record_id,
    seed.metric_label,
    seed.metric_value,
    seed.metric_count,
    seed.status_flag,
    seed.external_reference_id,
    seed.notes
FROM (
    SELECT 'agent_margin' AS snapshot_type, 'AGT-CT2-001' AS reference_code, 'agents' AS source_module, a.ct2_agent_id AS source_record_id, 'Commission Exposure' AS metric_label, 22000.00 AS metric_value, 1 AS metric_count, 'ok' AS status_flag, a.external_payment_id AS external_reference_id, 'Approved agent with live external payment reference.' AS notes
    FROM ct2_agents AS a
    WHERE a.agent_code = 'AGT-CT2-001'
    UNION ALL
    SELECT 'supplier_exposure', 'SUP-CT2-002', 'suppliers', s.ct2_supplier_id, 'Contract Exposure', 4800.00, 1, 'warning', s.external_supplier_id, 'Pending supplier approval keeps onboarding in review.' 
    FROM ct2_suppliers AS s
    WHERE s.supplier_code = 'SUP-CT2-002'
    UNION ALL
    SELECT 'resource_margin', 'CT1-BKG-1001', 'availability', ra.ct2_allocation_id, 'Reserved Resource Cost', 9200.00, 14, 'ok', ra.external_booking_id, 'Dispatch-ready allocation seeded for QA.'
    FROM ct2_resource_allocations AS ra
    WHERE ra.external_booking_id = 'CT1-BKG-1001'
    UNION ALL
    SELECT 'campaign_roi', 'CT2-MKT-001', 'marketing', c.ct2_campaign_id, 'Attributed Revenue', 356000.00, 39, 'ok', c.external_customer_segment_id, 'Seeded active campaign with completed revenue tracking.'
    FROM ct2_campaigns AS c
    WHERE c.campaign_code = 'CT2-MKT-001'
    UNION ALL
    SELECT 'visa_fee_status', 'VISA-APP-002', 'visa', va.ct2_visa_application_id, 'Outstanding Visa Fees', 6200.00, 1, 'critical', va.application_reference, 'Escalated visa case remains unpaid and pending approval.'
    FROM ct2_visa_applications AS va
    WHERE va.application_reference = 'VISA-APP-002'
) AS seed
INNER JOIN ct2_report_runs AS rr ON rr.run_label = 'QA Baseline Cross-Module Run'
WHERE NOT EXISTS (
    SELECT 1
    FROM ct2_financial_snapshots AS fs
    WHERE fs.ct2_report_run_id = rr.ct2_report_run_id
      AND fs.reference_code = seed.reference_code
      AND fs.metric_label = seed.metric_label
);

INSERT INTO ct2_reconciliation_flags (
    ct2_report_run_id, flag_type, source_module, source_record_id, severity,
    flag_status, flag_summary, resolution_notes, resolved_by, created_at, resolved_at
)
SELECT
    rr.ct2_report_run_id,
    seed.flag_type,
    seed.source_module,
    seed.source_record_id,
    seed.severity,
    'open',
    seed.flag_summary,
    NULL,
    NULL,
    NOW() - INTERVAL 1 DAY,
    NULL
FROM (
    SELECT 'supplier_approval_gap' AS flag_type, 'suppliers' AS source_module, s.ct2_supplier_id AS source_record_id, 'medium' AS severity, 'Harborview Suites is still pending approval while onboarding is in review.' AS flag_summary
    FROM ct2_suppliers AS s
    WHERE s.supplier_code = 'SUP-CT2-002'
    UNION ALL
    SELECT 'visa_payment_gap', 'visa', va.ct2_visa_application_id, 'high', 'VISA-APP-002 has an unpaid seeded visa fee and an open exception approval.'
    FROM ct2_visa_applications AS va
    WHERE va.application_reference = 'VISA-APP-002'
) AS seed
INNER JOIN ct2_report_runs AS rr ON rr.run_label = 'QA Baseline Cross-Module Run'
WHERE NOT EXISTS (
    SELECT 1
    FROM ct2_reconciliation_flags AS rf
    WHERE rf.ct2_report_run_id = rr.ct2_report_run_id
      AND rf.flag_type = seed.flag_type
      AND rf.source_record_id = seed.source_record_id
);
