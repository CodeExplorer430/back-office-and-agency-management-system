<?php

declare(strict_types=1);

final class CT2_AssignmentModel extends CT2_BaseModel
{
    public function getAll(): array
    {
        $ct2Statement = $this->ct2Pdo->query(
            'SELECT a.*, ag.agency_name, ag.agent_code, st.full_name, st.staff_code
             FROM ct2_agent_staff_assignments AS a
             INNER JOIN ct2_agents AS ag ON ag.ct2_agent_id = a.ct2_agent_id
             INNER JOIN ct2_staff AS st ON st.ct2_staff_id = a.ct2_staff_id
             ORDER BY a.created_at DESC'
        );

        return $ct2Statement->fetchAll();
    }

    public function create(array $ct2Payload): void
    {
        $ct2Statement = $this->ct2Pdo->prepare(
            'INSERT INTO ct2_agent_staff_assignments (
                ct2_agent_id, ct2_staff_id, assignment_role, assignment_status,
                start_date, end_date, notes
             ) VALUES (
                :ct2_agent_id, :ct2_staff_id, :assignment_role, :assignment_status,
                :start_date, :end_date, :notes
             )'
        );
        $ct2Statement->execute(
            [
                'ct2_agent_id' => $ct2Payload['ct2_agent_id'],
                'ct2_staff_id' => $ct2Payload['ct2_staff_id'],
                'assignment_role' => $ct2Payload['assignment_role'],
                'assignment_status' => $ct2Payload['assignment_status'],
                'start_date' => $ct2Payload['start_date'],
                'end_date' => $ct2Payload['end_date'] ?: null,
                'notes' => $ct2Payload['notes'] ?: null,
            ]
        );
    }
}
