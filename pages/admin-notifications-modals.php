<?php
// Filepath: /pages/admin-notifications-modals.php

/**
 * We assume $membershipTypes is already available from admin-notifications.php.
 * Similarly, the "edit notification" form references the same handler with action=update_notification.
 */
?>

<!-- New Notification Modal -->
<div class="modal fade" id="newNotificationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="post" action="admin-notifications-handler.php">
                <input type="hidden" name="action" value="create_notification">

                <div class="modal-header">
                    <h5 class="modal-title">Create New Notification</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label class="form-label">Title <span class="text-danger">*</span></label>
                                <input type="text" name="title" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Message <span class="text-danger">*</span></label>
                                <textarea name="message" class="form-control" rows="5" required></textarea>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Schedule</label>
                                <div class="mb-3">
                                    <label class="form-label small">Start Date (optional)</label>
                                    <input type="date" name="start_date" class="form-control form-control-sm">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small">Expiry Date (optional)</label>
                                    <input type="date" name="expiry_date" class="form-control form-control-sm">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Target Audience</label>
                                <div class="form-text small mb-2">Select specific membership types</div>
                                <div class="list-group list-group-flush">
                                    <?php if (!empty($membershipTypes)): ?>
                                        <?php foreach ($membershipTypes as $type): ?>
                                            <label class="list-group-item d-flex gap-2">
                                                <input class="form-check-input flex-shrink-0"
                                                    type="checkbox"
                                                    name="membership_types[]"
                                                    value="<?= $type['membership_type_id'] ?>">
                                                <span>
                                                    <?= htmlspecialchars($type['type_name']) ?>
                                                    <?php if ($type['can_access_exclusive']): ?>
                                                        <span class="badge bg-info ms-1">Exclusive</span>
                                                    <?php endif; ?>
                                                </span>
                                            </label>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <p>No membership types found.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div><!-- /row -->
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Send Notification</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- New Template Modal -->
<div class="modal fade" id="newTemplateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="admin-notifications-handler.php">
                <input type="hidden" name="action" value="save_template">
                <div class="modal-header">
                    <h5 class="modal-title">Save New Template</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Template Title <span class="text-danger">*</span></label>
                        <input type="text" name="template_title" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Template Message <span class="text-danger">*</span></label>
                        <textarea name="template_message" class="form-control" rows="8" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Template</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Notification Modal -->
<div class="modal fade" id="editNotificationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="post" action="admin-notifications-handler.php">
                <input type="hidden" name="action" value="update_notification">
                <input type="hidden" name="notification_id" id="editNotificationId">

                <div class="modal-header">
                    <h5 class="modal-title">Edit Notification</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label class="form-label">Title <span class="text-danger">*</span></label>
                                <input type="text" name="title" id="editTitle" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Message <span class="text-danger">*</span></label>
                                <textarea name="message" id="editMessage" class="form-control" rows="5" required></textarea>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Schedule</label>
                                <div class="mb-3">
                                    <label class="form-label small">Start Date</label>
                                    <input type="date" name="start_date" id="editStartDate" class="form-control form-control-sm">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small">Expiry Date</label>
                                    <input type="date" name="expiry_date" id="editExpiryDate" class="form-control form-control-sm">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Target Audience</label>
                                <div class="form-text small">Targets cannot be modified after creation</div>
                            </div>
                        </div>
                    </div><!-- /row -->
                </div>

                <div class="modal-footer justify-content-end">

                    <div>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Notification</button>
                    </div>
                </div>

            </form>
        </div>
    </div>
</div>