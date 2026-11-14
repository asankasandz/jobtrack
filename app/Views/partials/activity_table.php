<?php
// Table of stage changes, used by the admin dashboard ("Latest activity") and the report page.
// The including page sets $tableRows (rows from Report::stageReport()) first.
$resultPill = ['Applied' => 'pill-info', 'Passed' => 'pill-ok', 'Failed' => 'pill-bad', 'Withdrawn' => 'pill-muted'];
?>
<div class="tbl-wrap">
  <table class="tbl">
    <thead>
      <tr><th>Date</th><th>Applicant</th><th>Vacancy</th><th>Stage</th><th>Result</th><th>Note</th><th>Decided by</th></tr>
    </thead>
    <tbody>
      <?php foreach ($tableRows as $row):
          $result = Report::outcome($row['from_status'], $row['to_status']);
          $when = strtotime($row['changed_at']); ?>
        <tr>
          <td class="nowrap"><b><?= date('j M Y', $when) ?></b><small><?= date('g:i a', $when) ?></small></td>
          <td>
            <div class="user-cell">
              <span class="avatar-circle sm"><?= htmlspecialchars(strtoupper(substr($row['applicant_name'], 0, 1))) ?></span>
              <div><b><?= htmlspecialchars($row['applicant_name']) ?></b><small><?= htmlspecialchars($row['applicant_email']) ?></small></div>
            </div>
          </td>
          <td><?= htmlspecialchars($row['vacancy_title']) ?></td>
          <td><?= htmlspecialchars(Report::stageText($row['from_status'], $row['to_status'])) ?></td>
          <td><span class="pill <?= $resultPill[$result] ?>"><?= $result ?></span></td>
          <?php // The first row's note is automatic ("Application submitted."), so only show notes written by HR. ?>
          <td class="note-cell"><?= ($row['note'] && $row['from_status'] !== null) ? nl2br(htmlspecialchars($row['note'])) : '<span class="muted">&mdash;</span>' ?></td>
          <td><?= htmlspecialchars($row['changed_by_name']) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
