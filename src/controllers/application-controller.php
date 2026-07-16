<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/admin-user-controller.php';
require_once __DIR__ . '/../services/audit-log-service.php';

class ApplicationController
{
    public static function all(): array
    {
        $query = "SELECT
                    applications.application_id,
                    CONCAT_WS(
                        ' ',
                        applications.first_name,
                        applications.middle_name,
                        applications.last_name,
                        applications.suffix
                    ) AS complete_name,
                    applications.email,
                    applications.phone,
                    roles.role_name AS requested_role,
                    applications.reason,
                    applications.experience,
                    applications.availability,
                    applications.status,
                    applications.created_at
                  FROM staff_applications applications
                  JOIN roles ON roles.role_id = applications.requested_role_id
                  ORDER BY
                    FIELD(applications.status, 'pending', 'approved', 'rejected'),
                    applications.created_at DESC";

        return getDbConnection()->query($query)->fetchAll();
    }

    public static function review(
        int $applicationId,
        string $newStatus,
        int $administratorId
    ): array {
        if (!in_array($newStatus, ['approved', 'rejected'], true)) {
            http_response_code(422);
            return ['error' => 'Review status must be approved or rejected'];
        }

        $database = getDbConnection();
        $application = self::findApplication($database, $applicationId);

        if (!$application) {
            http_response_code(404);
            return ['error' => 'Application not found'];
        }

        if ($application['status'] !== 'pending') {
            http_response_code(422);
            return ['error' => 'Application was already reviewed'];
        }

        $createdUserId = null;
        $accountSetup = null;

        if ($newStatus === 'approved') {
            $applicant = self::getApplicantAccountDetails($database, $applicationId);
            $accountSetup = AdminUserController::create([
                'name' => $applicant['name'],
                'email' => $applicant['email'],
                'role' => $applicant['role'],
                'status' => 'Active',
            ], $administratorId);
            $createdUserId = $accountSetup['user_id'];
        }

        self::saveReview(
            $database,
            $applicationId,
            $newStatus,
            $administratorId,
            $createdUserId
        );
        self::recordAudit(
            $database,
            $administratorId,
            $applicationId,
            $newStatus,
            $application['email']
        );

        return [
            'success' => true,
            'account_setup' => $accountSetup,
        ];
    }

    private static function findApplication(PDO $database, int $applicationId): array|false
    {
        $statement = $database->prepare(
            'SELECT email, status
             FROM staff_applications
             WHERE application_id = :application_id'
        );
        $statement->execute(['application_id' => $applicationId]);

        return $statement->fetch();
    }

    private static function getApplicantAccountDetails(
        PDO $database,
        int $applicationId
    ): array {
        $statement = $database->prepare(
            "SELECT
                CONCAT_WS(' ', first_name, middle_name, last_name, suffix) AS name,
                email,
                roles.role_name AS role
             FROM staff_applications
             JOIN roles ON roles.role_id = staff_applications.requested_role_id
             WHERE application_id = :application_id"
        );
        $statement->execute(['application_id' => $applicationId]);

        return $statement->fetch();
    }

    private static function saveReview(
        PDO $database,
        int $applicationId,
        string $status,
        int $administratorId,
        ?int $createdUserId
    ): void {
        $statement = $database->prepare(
            'UPDATE staff_applications
             SET status = :status,
                 reviewed_by = :administrator_id,
                 reviewed_at = NOW(),
                 created_user_id = :created_user_id
             WHERE application_id = :application_id'
        );
        $statement->execute([
            'status' => $status,
            'administrator_id' => $administratorId,
            'created_user_id' => $createdUserId,
            'application_id' => $applicationId,
        ]);
    }

    private static function recordAudit(
        PDO $database,
        int $administratorId,
        int $applicationId,
        string $status,
        string $applicantEmail
    ): void {
        AuditLogService::record(
            $administratorId,
            'application.review',
            'staff_applications',
            $applicationId,
            ucfirst($status) . ' staff application: ' . $applicantEmail,
            $database
        );
    }
}
