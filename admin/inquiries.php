<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin();
if (!hasRole('admin')) redirect('/index.php');

$db = getDB();

$stmt = $db->query(
    "SELECT iq.*, l.title AS listing_title, l.id AS listing_id, u.name AS agent_name
     FROM inquiries iq
     JOIN listings l ON l.id = iq.listing_id
     JOIN users u ON u.id = l.agent_id
     ORDER BY iq.created_at DESC"
);
$inquiries = $stmt->fetchAll();

$pageTitle = 'All Inquiries — Admin';
include __DIR__ . '/../includes/header.php';
?>

<div style="display:flex;min-height:calc(100vh - 68px);">
    <?php include __DIR__ . '/partials/sidebar.php'; ?>

    <main style="flex:1;padding:32px;overflow:auto;">
        <div class="dashboard-header">
            <h1>All Inquiries</h1>
            <p><?= count($inquiries) ?> total inquiries received</p>
        </div>

        <?php if ($inquiries): ?>
        <div class="data-table-wrap">
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr><th>From</th><th>Email</th><th>Listing</th><th>Agent</th><th>Message</th><th>Date</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($inquiries as $iq): ?>
                        <tr>
                            <td class="font-semibold text-sm"><?= e($iq['sender_name']) ?></td>
                            <td><a href="mailto:<?= e($iq['sender_email']) ?>" style="color:var(--blue);font-size:.85rem;"><?= e($iq['sender_email']) ?></a></td>
                            <td>
                                <a href="<?= SITE_URL ?>/listings/view.php?id=<?= (int)$iq['listing_id'] ?>"
                                   style="color:var(--terra);font-size:.85rem;font-weight:600;">
                                    <?= e($iq['listing_title']) ?>
                                </a>
                            </td>
                            <td class="text-sm text-gray"><?= e($iq['agent_name']) ?></td>
                            <td>
                                <span style="font-size:.82rem;color:var(--gray-700);" title="<?= e($iq['message']) ?>">
                                    <?= e(mb_substr($iq['message'], 0, 60)) ?><?= mb_strlen($iq['message']) > 60 ? '…' : '' ?>
                                </span>
                            </td>
                            <td class="text-xs text-gray"><?= date('M j, Y', strtotime($iq['created_at'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <i data-lucide="message-circle"></i>
            <h3>No inquiries yet</h3>
            <p>Inquiries submitted on listing pages will appear here.</p>
        </div>
        <?php endif; ?>
    </main>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
