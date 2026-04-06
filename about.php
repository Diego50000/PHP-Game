<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>PokéQuest — About</title>
  <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
  <style>
    :root {
      --bg:       #1a1a2e;
      --surface:  rgba(22,33,62,.97);
      --gold:     #fbbf24;
      --gold-dim: #92400e;
      --purple:   #7c3aed;
      --purple-l: #c4b5fd;
      --green:    #4ade80;
      --red:      #ef4444;
      --blue:     #38bdf8;
      --text:     #e0e0f0;
      --muted:    #a0a0c0;
      --border:   rgba(255,255,255,.08);
    }
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    html { scroll-behavior: smooth; }
    body {
      background-color: var(--bg);
      background-image:
        radial-gradient(ellipse at 10% 30%, rgba(124,58,237,.2) 0%, transparent 55%),
        radial-gradient(ellipse at 90% 10%, rgba(56,189,248,.1) 0%, transparent 50%),
        radial-gradient(ellipse at 50% 95%, rgba(251,191,36,.08) 0%, transparent 50%);
      min-height: 100vh;
      font-family: 'Nunito', sans-serif;
      color: var(--text);
      overflow-x: hidden;
    }
    body::before {
      content: ''; position: fixed; inset: 0; pointer-events: none; z-index: 0;
      background-image:
        linear-gradient(rgba(255,255,255,.012) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,.012) 1px, transparent 1px);
      background-size: 32px 32px;
    }

    /* NAV */
    nav {
      position: sticky; top: 0; z-index: 100;
      background: rgba(8,10,24,.93);
      border-bottom: 2px solid rgba(251,191,36,.25);
      backdrop-filter: blur(14px);
      padding: 12px 28px;
      display: flex; align-items: center; gap: 10px; flex-wrap: wrap;
    }
    .nav-logo { font-family:'Press Start 2P',monospace; font-size:11px; color:var(--gold); text-shadow:2px 2px 0 var(--gold-dim); margin-right:auto; }
    nav a { text-decoration:none; color:var(--muted); border:1px solid var(--border); padding:6px 14px; border-radius:20px; font-size:13px; transition:all .2s; }
    nav a:hover, nav a.active { border-color:var(--purple); color:var(--purple-l); background:rgba(124,58,237,.12); }

    /* LAYOUT */
    .wrapper { position:relative; z-index:1; max-width:880px; margin:0 auto; padding:56px 24px 100px; }

    /* HERO */
    .hero { text-align:center; margin-bottom:72px; animation:fadeUp .7s ease both; }
    .hero-dragon { font-size:4.5rem; display:block; margin-bottom:18px; animation:dragonFloat 3s ease-in-out infinite; }
    @keyframes dragonFloat { 0%,100%{transform:translateY(0) rotate(-2deg);} 50%{transform:translateY(-12px) rotate(2deg);} }
    .hero h1 { font-family:'Press Start 2P',monospace; font-size:clamp(1.3rem,3.5vw,2rem); color:var(--gold); text-shadow:3px 3px 0 var(--gold-dim),0 0 50px rgba(251,191,36,.3); line-height:1.6; margin-bottom:14px; }
    .hero-sub { color:var(--muted); font-size:14px; max-width:520px; margin:0 auto 24px; line-height:1.8; }
    .hero-badges { display:flex; gap:8px; justify-content:center; flex-wrap:wrap; }
    .badge { background:rgba(22,33,62,.9); border:1px solid var(--border); border-radius:999px; padding:5px 14px; font-size:12px; color:var(--muted); }
    .badge b { color:var(--gold); }
    @keyframes fadeUp { from{opacity:0;transform:translateY(28px);} to{opacity:1;transform:none;} }

    /* SECTIONS */
    .section { margin-bottom:64px; }
    .section-title { font-family:'Press Start 2P',monospace; font-size:10px; letter-spacing:3px; color:var(--gold); display:flex; align-items:center; gap:12px; margin-bottom:24px; }
    .section-title::after { content:''; flex:1; height:1px; background:linear-gradient(90deg,rgba(251,191,36,.35),transparent); }

    /* CARD */
    .card { background:var(--surface); border:1px solid rgba(251,191,36,.12); border-radius:14px; padding:28px; transition:border-color .2s; }
    .card:hover { border-color:rgba(251,191,36,.3); }

    /* RULES GRID */
    .rules-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(240px,1fr)); gap:14px; }
    .rule-card { background:rgba(15,52,96,.45); border:1px solid rgba(124,58,237,.25); border-radius:10px; padding:20px; transition:border-color .2s,transform .2s; }
    .rule-card:hover { border-color:var(--purple); transform:translateY(-3px); }
    .rule-icon { font-size:2rem; display:block; margin-bottom:12px; }
    .rule-card h3 { font-size:13px; font-weight:800; color:var(--gold); margin-bottom:8px; }
    .rule-card p { font-size:12px; color:var(--muted); line-height:1.8; }
    .rule-tag { display:inline-block; margin-top:10px; font-size:10px; font-weight:700; padding:2px 9px; border-radius:999px; }
    .tag-danger { background:rgba(239,68,68,.15); color:var(--red); border:1px solid rgba(239,68,68,.3); }
    .tag-info   { background:rgba(56,189,248,.12); color:var(--blue); border:1px solid rgba(56,189,248,.3); }
    .tag-good   { background:rgba(74,222,128,.12); color:var(--green); border:1px solid rgba(74,222,128,.3); }

    /* STEPS */
    .steps { display:flex; flex-direction:column; gap:0; }
    .step { display:flex; gap:20px; align-items:flex-start; position:relative; padding-bottom:28px; }
    .step:last-child { padding-bottom:0; }
    .step:not(:last-child)::before { content:''; position:absolute; left:20px; top:44px; bottom:0; width:2px; background:linear-gradient(180deg,rgba(124,58,237,.4),transparent); }
    .step-num { width:40px; height:40px; flex-shrink:0; background:rgba(124,58,237,.2); border:2px solid rgba(124,58,237,.5); border-radius:50%; display:flex; align-items:center; justify-content:center; font-family:'Press Start 2P',monospace; font-size:9px; color:var(--purple-l); }
    .step-body { flex:1; padding-top:8px; }
    .step-body h4 { font-size:14px; font-weight:800; color:var(--text); margin-bottom:5px; }
    .step-body p { font-size:13px; color:var(--muted); line-height:1.75; }
    .hint { display:inline-flex; align-items:center; gap:5px; margin-top:8px; font-size:11px; color:var(--blue); background:rgba(56,189,248,.08); border:1px solid rgba(56,189,248,.2); border-radius:6px; padding:4px 10px; }

    /* CONTROLS */
    .controls-row { display:flex; flex-wrap:wrap; gap:12px; align-items:center; }
    .key-group { display:flex; align-items:center; gap:6px; }
    .key { background:rgba(255,255,255,.07); border:1px solid rgba(255,255,255,.18); border-radius:5px; padding:4px 10px; font-family:'Press Start 2P',monospace; font-size:8px; color:var(--text); box-shadow:0 3px 0 rgba(0,0,0,.5); white-space:nowrap; }
    .key-label { font-size:12px; color:var(--muted); }
    .key-divider { width:1px; height:28px; background:var(--border); margin:0 4px; }

    /* CREDITS */
    .credits-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:14px; }
    .credit-card { background:rgba(15,52,96,.4); border:1px solid var(--border); border-radius:10px; padding:20px; text-align:center; transition:border-color .2s,transform .2s; }
    .credit-card:hover { border-color:var(--purple-l); transform:translateY(-3px); }
    .credit-avatar { font-size:2.8rem; display:block; margin-bottom:10px; }
    .credit-role { font-family:'Press Start 2P',monospace; font-size:7px; color:var(--purple-l); letter-spacing:1px; margin-bottom:8px; display:block; }
    .credit-name { font-size:15px; font-weight:800; color:var(--text); display:block; margin-bottom:4px; }
    .credit-desc { font-size:11px; color:var(--muted); line-height:1.6; }

    /* AI ATTRIBUTION */
    .ai-card { background:rgba(124,58,237,.06); border:1px solid rgba(124,58,237,.25); border-radius:14px; padding:28px; }
    .ai-header { display:flex; gap:16px; align-items:center; margin-bottom:20px; }
    .ai-logo { width:52px; height:52px; flex-shrink:0; background:rgba(124,58,237,.2); border:2px solid rgba(196,181,253,.3); border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:1.8rem; }
    .ai-header h3 { font-size:16px; font-weight:800; color:var(--text); margin-bottom:3px; }
    .ai-header span { font-size:12px; color:var(--muted); }
    .ai-details { display:flex; flex-direction:column; gap:12px; }
    .ai-row { display:flex; gap:12px; align-items:flex-start; padding:12px 14px; background:rgba(0,0,0,.2); border-radius:8px; border-left:3px solid rgba(124,58,237,.5); }
    .ai-row-icon { font-size:1.2rem; flex-shrink:0; margin-top:1px; }
    .ai-row-body h5 { font-size:12px; font-weight:800; color:var(--purple-l); margin-bottom:3px; }
    .ai-row-body p  { font-size:12px; color:var(--muted); line-height:1.7; }
    .ai-disclaimer { margin-top:18px; padding:12px 16px; background:rgba(251,191,36,.05); border:1px solid rgba(251,191,36,.15); border-radius:8px; font-size:11px; color:#888; line-height:1.7; }
    .ai-disclaimer b { color:var(--gold); }

    /* REVEAL */
    .reveal { opacity:0; transform:translateY(22px); transition:opacity .6s ease,transform .6s ease; }
    .reveal.visible { opacity:1; transform:none; }

    /* FOOTER */
    footer { position:relative; z-index:1; text-align:center; padding:32px 24px; border-top:1px solid rgba(251,191,36,.1); font-size:11px; color:#444; }
    footer span { color:var(--gold); }
  </style>
</head>
<body>

<nav>
  <span class="nav-logo">⚡ PokéQuest</span>
  <a href="index.php">🏠 Play</a>
  <a href="leaderboard.php">🏆 Leaderboard</a>
  <a href="about.php" class="active">📖 About</a>
</nav>

<div class="wrapper">

  <!-- HERO -->
  <div class="hero">
    <span class="hero-dragon">🐲</span>
    <h1>About PokéQuest</h1>
    <p class="hero-sub">A browser-based creature-catching RPG built from scratch. Battle across 5 zones, catch creatures, defeat trainers, and face the legendary MALACHAR to win.</p>
    <div class="hero-badges">
      <div class="badge">⚔️ <b>5</b> Zones</div>
      <div class="badge">🐾 <b>19</b> Creatures</div>
      <div class="badge">🏆 Live Leaderboard</div>
      <div class="badge">💾 Save System</div>
      <div class="badge">💀 Permadeath</div>
    </div>
  </div>

  <!-- ═══════════════════════ GAME RULES ═══════════════════════ -->
  <div class="section reveal">
    <div class="section-title">GAME RULES</div>
    <div class="rules-grid">

      <div class="rule-card">
        <span class="rule-icon">🎯</span>
        <h3>Objective</h3>
        <p>Travel through all 5 zones, defeat every trainer, and beat the legendary MALACHAR in Zone 5 to win and claim your spot on the leaderboard.</p>
        <span class="rule-tag tag-good">Win Condition</span>
      </div>

      <div class="rule-card">
        <span class="rule-icon">💀</span>
        <h3>Permadeath</h3>
        <p>If every creature in your party faints, your run ends. Your score is saved to the leaderboard, but your account is deleted and you start completely from scratch.</p>
        <span class="rule-tag tag-danger">Permanent</span>
      </div>

      <div class="rule-card">
        <span class="rule-icon">🎒</span>
        <h3>Party Limit</h3>
        <p>You can carry up to 6 creatures. There is no box — if you catch something when your party is full you must immediately release one member forever.</p>
        <span class="rule-tag tag-info">Max 6</span>
      </div>

      <div class="rule-card">
        <span class="rule-icon">🚪</span>
        <h3>Zone Progression</h3>
        <p>Zones unlock in order through golden warp doors. Each zone has a trainer blocking the exit — you must defeat them to advance to the next area.</p>
        <span class="rule-tag tag-info">Linear</span>
      </div>

      <div class="rule-card">
        <span class="rule-icon">🐲</span>
        <h3>The Legendary</h3>
        <p>MALACHAR cannot be caught and cannot be fled from. It has 420 HP and three increasingly aggressive phases. You must defeat it to complete the game.</p>
        <span class="rule-tag tag-danger">Uncatchable</span>
      </div>

      <div class="rule-card">
        <span class="rule-icon">🏆</span>
        <h3>Scoring</h3>
        <p>Wild win: <b style="color:var(--gold)">+100pts</b> &nbsp;·&nbsp; Trainer win: <b style="color:var(--gold)">+500pts</b> &nbsp;·&nbsp; Boss: <b style="color:var(--gold)">+2000pts</b> &nbsp;·&nbsp; Completion bonus: <b style="color:var(--gold)">+5000pts</b></p>
        <span class="rule-tag tag-good">Leaderboard</span>
      </div>

    </div>
  </div>

  <!-- ═══════════════════════ HOW TO PLAY ═══════════════════════ -->
  <div class="section reveal">
    <div class="section-title">HOW TO PLAY</div>
    <div class="card">
      <div class="steps">

        <div class="step">
          <div class="step-num">01</div>
          <div class="step-body">
            <h4>Choose Your Starter</h4>
            <p>On your first login, Professor Oak gives you a choice of three starter creatures. <b style="color:var(--green)">LEAFLING</b> (Grass — high DEF), <b style="color:var(--red)">EMBRITE</b> (Fire — very high ATK), or <b style="color:var(--blue)">TORRTLE</b> (Water — very high DEF). Your starter stays with you for the entire run.</p>
            <span class="hint">💡 Embrite is the glass cannon — highest ATK but lowest DEF in the game.</span>
          </div>
        </div>

        <div class="step">
          <div class="step-num">02</div>
          <div class="step-body">
            <h4>Explore & Move</h4>
            <p>Use <b>WASD</b> or the <b>Arrow Keys</b> to walk around the map. Dark tall grass triggers random wild battles. Golden glowing tiles are warp doors to the next zone. Pink glowing tiles are healing springs — step on them to fully restore your whole party.</p>
            <span class="hint">💡 Explore the full map before warping forward — you might miss creatures or the healing spring.</span>
          </div>
        </div>

        <div class="step">
          <div class="step-num">03</div>
          <div class="step-body">
            <h4>Battle Wild Creatures</h4>
            <p>Pick a move from the <b>Fight</b> tab to attack. Weaken the enemy then open the <b>Bag</b> tab and throw a Poké Ball to catch it — the lower their HP the better your catch chance. You can also <b>Run</b> from wild encounters or use a <b>Potion</b> to heal mid-fight.</p>
            <span class="hint">💡 Great Balls (🟣) have a significantly higher catch rate than regular Poké Balls.</span>
          </div>
        </div>

        <div class="step">
          <div class="step-num">04</div>
          <div class="step-body">
            <h4>Manage Your Items</h4>
            <p>You start with 5 Poké Balls, 5 Potions, and 5 Revives. Potions heal 30 HP to your lead creature (costs a turn in battle). Revives bring a fainted creature back to 50% HP. Outside battle, click the 🧪 or 💊 buttons in the party panel on the right side.</p>
            <span class="hint">💡 Save your revives for Zone 5 — you will need them for the final boss fight.</span>
          </div>
        </div>

        <div class="step">
          <div class="step-num">05</div>
          <div class="step-body">
            <h4>Face Trainers</h4>
            <p>Walking into a trainer's line of sight triggers a forced battle — no catching, no fleeing. Win to earn item rewards and unlock the zone exit. If all your creatures faint against a trainer, it's game over. Trainers have a dotted sight line shown on the map.</p>
            <span class="hint">💡 Each trainer gets harder: Zone 5's Shadow Lord has 4 creatures all at level 38–41.</span>
          </div>
        </div>

        <div class="step">
          <div class="step-num">06</div>
          <div class="step-body">
            <h4>Defeat MALACHAR to Win</h4>
            <p>In Zone 5, beat <b>Shadow Lord Kael</b> to unlock the north exit. Step on the warp tile to trigger the legendary battle. MALACHAR has 3 phases — it gets more aggressive as its HP drops. Defeat it to clear the game, earn a 5000pt completion bonus, and lock in your leaderboard score.</p>
            <span class="hint">💡 MALACHAR phase 3 (below 25% HP) alternates Fissure and Shadow Blast every single turn. Heal early.</span>
          </div>
        </div>

      </div>

      <!-- Controls quick reference -->
      <div style="margin-top:28px;padding-top:22px;border-top:1px solid var(--border);">
        <div style="font-size:11px;color:var(--muted);margin-bottom:12px;letter-spacing:1px;font-weight:700;">CONTROLS</div>
        <div class="controls-row">
          <div class="key-group">
            <span class="key">W</span><span class="key">A</span><span class="key">S</span><span class="key">D</span>
            <span class="key-label">Move</span>
          </div>
          <div class="key-divider"></div>
          <div class="key-group">
            <span class="key">↑</span><span class="key">↓</span><span class="key">←</span><span class="key">→</span>
            <span class="key-label">Move</span>
          </div>
          <div class="key-divider"></div>
          <div class="key-group">
            <span class="key">SPACE</span>
            <span class="key-label">Advance dialogue</span>
          </div>
          <div class="key-divider"></div>
          <div class="key-group">
            <span class="key">ESC</span>
            <span class="key-label">Unstick if frozen</span>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- ═══════════════════════ CREDITS ═══════════════════════ -->
  <div class="section reveal">
    <div class="section-title">CREDITS</div>
    <div class="credits-grid">

      <div class="credit-card">
        <span class="credit-avatar">👤</span>
        <span class="credit-role">GAME DESIGNER</span>
        <span class="credit-name">You</span>
        <span class="credit-desc">All creative decisions — zone design, game mechanics, creature names, the permadeath system, and the overall vision of PokéQuest.</span>
      </div>

      <div class="credit-card">
        <span class="credit-avatar">🤖</span>
        <span class="credit-role">AI CODING ASSISTANT</span>
        <span class="credit-name">Claude</span>
        <span class="credit-desc">All PHP, JavaScript, HTML and CSS was written by Claude (Anthropic) through conversational prompting. No game engine or code library was used.</span>
      </div>

      <div class="credit-card">
        <span class="credit-avatar">🛠️</span>
        <span class="credit-role">HOSTING PLATFORM</span>
        <span class="credit-name">Replit</span>
        <span class="credit-desc">Deployed and hosted on Replit. PHP backend, JSON file storage, and public URL all provided by the Replit platform.</span>
      </div>

      <div class="credit-card">
        <span class="credit-avatar">🎨</span>
        <span class="credit-role">TYPOGRAPHY</span>
        <span class="credit-name">Google Fonts</span>
        <span class="credit-desc">Press Start 2P by CodeMan38 for pixel-art headings. Nunito by Vernon Adams for all body text.</span>
      </div>

    </div>

    <div style="margin-top:14px;padding:16px 20px;background:rgba(15,52,96,.35);border:1px solid var(--border);border-radius:10px;font-size:12px;color:var(--muted);line-height:1.8;">
      🎮 PokéQuest is an <b style="color:var(--text)">independent fan project</b> created for educational and personal use. It is <b style="color:var(--text)">not affiliated with</b>, endorsed by, or connected to Nintendo, Game Freak, or The Pokémon Company in any way. All creature names, zones, and mechanics are original creations.
    </div>
  </div>

  <!-- ═══════════════════════ AI ATTRIBUTION ═══════════════════════ -->
  <div class="section reveal">
    <div class="section-title">AI ATTRIBUTION</div>
    <div class="ai-card">

      <div class="ai-header">
        <div class="ai-logo">🤖</div>
        <div>
          <h3>Built with Claude by Anthropic</h3>
          <span>Full transparency on AI-assisted development</span>
        </div>
      </div>

      <div class="ai-details">

        <div class="ai-row">
          <span class="ai-row-icon">💻</span>
          <div class="ai-row-body">
            <h5>Code Generation</h5>
            <p>80% of the source code — PHP backend, JavaScript game engine, HTML structure, and CSS styling — was written by <b>Claude Sonnet</b> (Anthropic) through a conversational prompting interface. No external game engines, frameworks, or code libraries were used. Everything runs on vanilla PHP and JavaScript.</p>
          </div>
        </div>

        <div class="ai-row">
          <span class="ai-row-icon">🎮</span>
          <div class="ai-row-body">
            <h5>Human Design Direction</h5>
            <p>All game design decisions were made by the human developer: zone names and layouts, creature names and stats, trainer characters, mechanics like permadeath and party limits, the scoring system, item balance, and every feature request. Claude implemented those decisions as working code — it did not design the game.</p>
          </div>
        </div>

        <div class="ai-row">
          <span class="ai-row-icon">🔧</span>
          <div class="ai-row-body">
            <h5>Iterative Debugging</h5>
            <p>Bugs were identified through real play-testing and fixed through conversation. Issues resolved included: a JavaScript temporal dead zone causing a black screen on load, broken battle switch panels, post-battle movement freezes, the permadeath account deletion system, and revive button rendering in the party panel.</p>
          </div>
        </div>

        <div class="ai-row">
          <span class="ai-row-icon">📄</span>
          <div class="ai-row-body">
            <h5>Tool &amp; Model</h5>
            <p>Claude was accessed via <b>claude.ai</b> — Anthropic's web interface. The model used was <b>Claude Sonnet 4</b>. The entire game was developed within a single extended conversation session, with the full codebase maintained in context throughout the build.</p>
          </div>
        </div>

      </div>

      <div class="ai-disclaimer">
        <b>Transparency statement:</b> This project is submitted with full acknowledgement that implementation code was AI-generated. The creative vision, game design, feature specification, play-testing, and deployment were all performed by the human developer. Claude acted as a code-writing tool — not as a co-designer or creative contributor.
      </div>

    </div>
  </div>

</div>

<footer>
  <span>PokéQuest</span> &nbsp;·&nbsp; Built with Claude by Anthropic &nbsp;·&nbsp; Not affiliated with Nintendo or The Pokémon Company
</footer>

<script>
  const io = new IntersectionObserver(entries => {
    entries.forEach(e => { if (e.isIntersecting) { e.target.classList.add('visible'); io.unobserve(e.target); } });
  }, { threshold: 0.08 });
  document.querySelectorAll('.reveal').forEach(el => io.observe(el));
</script>
</body>
</html>