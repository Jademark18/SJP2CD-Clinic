<?php
session_start();

if (isset($_SESSION['student_id'])) {
	require_once 'config/db.php';

	$student_account_id = (int) $_SESSION['student_id'];
	$account_stmt = $conn->prepare('SELECT patient_id FROM student_accounts WHERE id = ? LIMIT 1');
	$account_stmt->bind_param('i', $student_account_id);
	$account_stmt->execute();
	$account = $account_stmt->get_result()->fetch_assoc();
	$patient_id = (int) ($account['patient_id'] ?? 0);

	if ($patient_id > 0) {
		$conn->begin_transaction();

		$medicine_stmt = $conn->prepare('DELETE FROM medicine_usage WHERE patient_id = ?');
		$medicine_stmt->bind_param('i', $patient_id);
		$medicine_stmt->execute();

		$records_stmt = $conn->prepare('DELETE FROM medical_records WHERE patient_id = ?');
		$records_stmt->bind_param('i', $patient_id);
		$records_stmt->execute();

		$conn->commit();
	}
}

session_destroy();
header('Location: index.php?success=You+have+been+signed+out+successfully.');
exit();
?>
