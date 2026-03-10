<?php
// 1. Read the JSON file
$jsonData = file_get_contents('leaderboard.json');

// 2. Convert JSON data into a PHP array
$leaderboard = json_decode($jsonData, true);

// 3. Sort the array by score (highest to lowest) by default
if ($leaderboard) {
    usort($leaderboard, function($a, $b) {
        return $b['score'] <=> $a['score'];
    });
} else {
    $leaderboard = []; // Fallback if file is empty
}
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

    th {
      background: #0f3460;
      color: #fbbf24;
      cursor: pointer;
      font-weight: bold;
    }
    th:hover { background: #1a1a3e; }
  </style>
</head>
<body>

  <nav>
    <a href="index.php">⬅ Back to Login</a>
  </nav>

  <div class="logo">Leaderboard</div>

  <table id="leaderboardTable">
    <thead>
      <tr>
        <th onclick="sortTable(0)">Trainer Name ↕</th>
        <th onclick="sortTable(1)">Score ↕</th>
        <th onclick="sortTable(2)">Zones Cleared ↕</th>
        <th onclick="sortTable(3)">Creatures Defeated ↕</th>
        <th onclick="sortTable(4)">Time ↕</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($leaderboard as $player): ?>
        <tr>
          <td><?php echo htmlspecialchars($player['username']); ?></td>
          <td><?php echo htmlspecialchars($player['score']); ?></td>
          <td><?php echo htmlspecialchars($player['zonesCleared']); ?></td>
          <td><?php echo htmlspecialchars($player['creaturesDefeated']); ?></td>
          <td><?php echo htmlspecialchars($player['completionTime']); ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <script>
    function sortTable(columnIndex) {
      let table = document.getElementById("leaderboardTable");
      let rows = Array.from(table.rows).slice(1); // Get all rows except the header
      let isAscending = table.getAttribute("data-sort-dir") === "asc";

      rows.sort((rowA, rowB) => {
        let cellA = rowA.cells[columnIndex].innerText;
        let cellB = rowB.cells[columnIndex].innerText;

        // Check if the values are numbers so it sorts correctly
        let valA = isNaN(cellA) ? cellA.toLowerCase() : parseFloat(cellA);
        let valB = isNaN(cellB) ? cellB.toLowerCase() : parseFloat(cellB);

        if (valA < valB) return isAscending ? -1 : 1;
        if (valA > valB) return isAscending ? 1 : -1;
        return 0;
      });

      // Toggle sorting direction
      table.setAttribute("data-sort-dir", isAscending ? "desc" : "asc");

      // Re-add sorted rows to the table
      rows.forEach(row => table.appendChild(row));
    }
  </script>

</body>
</html>