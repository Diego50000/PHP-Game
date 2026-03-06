<html>
  <head>
    <title>Pokequest - Login</title>
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
        padding: 20px;
      }

      nav {
        width: 100%;
        max-width: 480px;
        display: flex;
        gap: 10px;
        justify-content: flex-end;
        margin-bottom: 30px;
      }

      nav a { text-decoration: none; }

      nav button {
        background: transparent;
        border: 1px solid rgba(255,255,255,0.2);
        color: #a0a0c0;
        font-family: 'Nunito', sans-serif;
        font-size: 13px;
        padding: 6px 16px;
        border-radius: 20px;
        cursor: pointer;
        transition: all 0.2s;
      }

      nav button:hover {
        border-color: #7c3aed;
        color: #c4b5fd;
        background: rgba(124,58,237,0.1);
      }

      .logo {
        font-family: 'Press Start 2P', monospace;
        font-size: 28px;
        color: #fbbf24;
        text-shadow: 3px 3px 0 #92400e, 0 0 30px rgba(251,191,36,0.4);
        margin-bottom: 8px;
        letter-spacing: 2px;
      }

      .subtitle {
        font-size: 13px;
        color: #6b7db3;
        margin-bottom: 40px;
        letter-spacing: 1px;
      }

      .card {
        background: rgba(255,255,255,0.04);
        border: 1px solid rgba(255,255,255,0.08);
        border-radius: 20px;
        padding: 36px 40px;
        width: 100%;
        max-width: 420px;
        backdrop-filter: blur(10px);
        box-shadow: 0 20px 60px rgba(0,0,0,0.4);
      }

      .form-group {
        margin-bottom: 20px;
      }

      label {
        display: block;
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1.5px;
        color: #8888aa;
        margin-bottom: 8px;
      }

      input[type="text"],
      input[type="password"],
      select {
        width: 100%;
        background: rgba(255,255,255,0.06);
        border: 1px solid rgba(255,255,255,0.12);
        border-radius: 10px;
        padding: 12px 16px;
        color: #e0e0f0;
        font-family: 'Nunito', sans-serif;
        font-size: 15px;
        transition: border-color 0.2s, box-shadow 0.2s;
        outline: none;
        -webkit-appearance: none;
      }

      input:focus, select:focus {
        border-color: #7c3aed;
        box-shadow: 0 0 0 3px rgba(124,58,237,0.2);
      }

      select option { background: #1a1a2e; }

      .btn-primary {
        width: 100%;
        margin-top: 10px;
        padding: 14px;
        background: linear-gradient(135deg, #7c3aed, #4f46e5);
        border: none;
        border-radius: 12px;
        color: white;
        font-family: 'Press Start 2P', monospace;
        font-size: 11px;
        letter-spacing: 1px;
        cursor: pointer;
        transition: transform 0.15s, box-shadow 0.15s;
        box-shadow: 0 4px 20px rgba(124,58,237,0.4);
      }

      .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 28px rgba(124,58,237,0.5);
      }

      .btn-primary:active { transform: translateY(0); }
    </style>
  </head>
  <body>
    <nav>
      <a href="about.php"><button type="button">About</button></a>
      <a href="leaderboard.php"><button type="button">Leaderboard</button></a>
    </nav>

    <div class="logo">Pokequest</div>
    <p class="subtitle">Your adventure awaits</p>

    <div class="card">
      <form action="game.php" method="POST">
        <div class="form-group">
          <label for="playerName">Trainer Name</label>
          <input type="text" id="playerName" name="playerName" placeholder="Enter your name..." required>
        </div>
        <div class="form-group">
          <label for="password">Password</label>
          <input type="password" id="password" name="password" placeholder="••••••••" required>
        </div>
        <div class="form-group">
          <label for="difficulty">Difficulty</label>
          <select id="difficulty" name="difficulty">
            <option value="easy">🌿 Easy</option>
            <option value="normal">⚔️ Normal</option>
            <option value="hard">🔥 Hard</option>
            <option value="very_hard">💀 Very Hard</option>
          </select>
        </div>
        <button class="btn-primary" type="submit">Start Adventure</button>
      </form>
    </div>
  </body>
</html>