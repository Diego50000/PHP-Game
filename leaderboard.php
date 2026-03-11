<?php
$jsonData    = file_get_contents(__DIR__ . '/data/leaderboard.json');
$leaderboard = json_decode($jsonData, true) ?? [];

usort($leaderboard, function($a, $b) {
    return $b['score'] <=> $a['score'];
});
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Pokequest - Leaderboard</title>
  <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      background-color: #1a1a2e;
      background-image:
        radial-gradient(ellipse at 20% 50%, rgba(99,0,210,0.15) 0%, transparent 60%),
        radial-gradient(ellipse at 80% 20%, rgba(0,180,216,0.12) 0%, transparent 50%);
      min-height: 100vh;
      font-family: 'Nunito', sans-serif;
      color: #e0e0f0;
      display: flex;
      flex-direction: column;
      align-items: center;
      padding: 40px 20px;
    }
    nav { width: 100%; max-width: 800px; margin-bottom: 20px; }
    nav a { text-decoration: none; color: #a0a0c0; border: 1px solid rgba(255,255,255,0.2); padding: 8px 16px; border-radius: 20px; }
    nav a:hover { border-color: #7c3aed; color: #c4b5fd; background: rgba(124,58,237,0.1); }
    .logo {
      font-family: 'Press Start 2P', monospace;
      font-size: 28px;
      color: #fbbf24;
      text-shadow: 3px 3px 0 #92400e, 0 0 30px rgba(251,191,36,0.4);
      margin-bottom: 30px;
    }
    .empty { color: #555; font-size: 14px; margin-top: 30px; }
    table {
      width: 100%;
      max-width: 800px;
      border-collapse: collapse;
      background: rgba(22,33,62,.97);
      border: 2px solid #7c3aed;
      border-radius: 8px;
      overflow: hidden;
    }
    th, td { padding: 12px 15px; text-align: left; }
    td { border-bottom: 1px solid #334; }
    th { background: #0f3460; color: #fbbf24; cursor: pointer; font-weight: bold; }
    th:hover { background: #1a1a3e; }
    tr:first-child td { color: #fbbf24; font-weight: bold; }
    .rank { color: #aaa; font-size: 13px; }
    tr:nth-child(1) .rank { color: #fbbf24; }
    tr:nth-child(2) .rank { color: #c0c0c0; }
    tr:nth-child(3) .rank { color: #cd7f32; }
  </style>
</head>
<body>
  <nav><a href="index.php">⬅ Back to Login</a></nav>
  <div class="logo">🏆 Leaderboard</div>

  <?php if (empty($leaderboard)): ?>
    <p class="empty">No scores yet — be the first to play!</p>
  <?php else: ?>
  <table id="leaderboardTable">
    <thead>
      <tr>
        <th>#</th>
        <th onclick="sortTable(1)">Trainer Name ↕</th>
        <th onclick="sortTable(2)">Score ↕</th>
        <th onclick="sortTable(3)">Zones Cleared ↕</th>
        <th onclick="sortTable(4)">Creatures Defeated ↕</th>
        <th onclick="sortTable(5)">Time ↕</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($leaderboard as $i => $entry): ?>
        <tr>
          <td class="rank">
            <?php
              if ($i === 0) echo '🥇';
              elseif ($i === 1) echo '🥈';
              elseif ($i === 2) echo '🥉';
              else echo '#' . ($i + 1);
            ?>
          </td>
          <td><?= htmlspecialchars($entry['username']) ?></td>
          <td><?= number_format($entry['score']) ?></td>
          <td><?= htmlspecialchars($entry['zonesCleared']) ?></td>
          <td><?= htmlspecialchars($entry['creaturesDefeated']) ?></td>
          <td><?= htmlspecialchars($entry['completionTime']) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>

  <script>
    function sortTable(colIndex) {
      const table = document.getElementById('leaderboardTable');
      const rows  = Array.from(table.rows).slice(1);
      const asc   = table.getAttribute('data-sort-dir') === 'asc';

      rows.sort((a, b) => {
        let va = a.cells[colIndex].innerText;
        let vb = b.cells[colIndex].innerText;
        // Strip commas from numbers
        va = va.replace(/,/g, '');
        vb = vb.replace(/,/g, '');
        const na = parseFloat(va), nb = parseFloat(vb);
        if (!isNaN(na) && !isNaN(nb)) return asc ? na - nb : nb - na;
        return asc ? va.localeCompare(vb) : vb.localeCompare(va);
      });

      table.setAttribute('data-sort-dir', asc ? 'desc' : 'asc');
      const tbody = table.querySelector('tbody');
      rows.forEach(r => tbody.appendChild(r));
    }
  </script>
</body>
</html>