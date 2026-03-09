<?php
// ============================================================
//  zone2.php — Zone 2: Rocky Peaks
//  Shares session with game.php (same session_start)
// ============================================================
session_start();

// Guard: must be logged in and in zone 2 to be here
if (empty($_SESSION['playerName'])) { header('Location: game.php'); exit; }

// ── HELPERS (duplicated from game.php so this file is self-contained) ─────────

function xpForLevel(int $level): int { return $level * $level * 10; }

function awardXP(array &$creature, int $xp): string {
    if (!isset($creature['level']))   $creature['level']   = 1;
    if (!isset($creature['xp']))      $creature['xp']      = 0;
    if (!isset($creature['xp_next'])) $creature['xp_next'] = xpForLevel(1);
    $creature['xp'] += $xp;
    $msg = '';
    while ($creature['xp'] >= $creature['xp_next']) {
        $creature['xp']    -= $creature['xp_next'];
        $creature['level'] += 1;
        $creature['max_hp']  = round($creature['max_hp']  * 1.08);
        $creature['hp']      = $creature['max_hp'];
        $creature['attack']  = round($creature['attack']  * 1.07);
        $creature['defense'] = round($creature['defense'] * 1.06);
        $creature['speed']   = round($creature['speed']   * 1.05);
        $creature['xp_next'] = xpForLevel($creature['level']);
        $msg .= "{$creature['name']} grew to Lv.{$creature['level']}! ";
    }
    return trim($msg);
}

function awardXPToActive(int $xp): string {
    if (empty($_SESSION['team'])) return '';
    $msg = awardXP($_SESSION['team'][0], $xp);
    $_SESSION['my_hp']     = $_SESSION['team'][0]['hp'];
    $_SESSION['my_max_hp'] = $_SESSION['team'][0]['max_hp'];
    $_SESSION['my_attack'] = $_SESSION['team'][0]['attack'];
    return $msg;
}

function syncActiveFromTeam(): void {
    if (!empty($_SESSION['team'])) {
        $_SESSION['my_hp']     = $_SESSION['team'][0]['hp'];
        $_SESSION['my_max_hp'] = $_SESSION['team'][0]['max_hp'];
        $_SESSION['my_attack'] = $_SESSION['team'][0]['attack'];
        $_SESSION['my_type']   = $_SESSION['team'][0]['type'];
    }
}

function getMovesForType(string $type): array {
    $moves = [
        'Grass'    => [
            'vinewhip'    => ['name'=>'Vine Whip',    'emoji'=>'🌿','mult'=>1.0,'desc'=>'A fast whipping vine attack.','power'=>'★★☆☆'],
            'leafblade'   => ['name'=>'Leaf Blade',   'emoji'=>'🍃','mult'=>1.4,'desc'=>'Sharp leaves slice the foe.','power'=>'★★★☆'],
            'solarbeam'   => ['name'=>'Solar Beam',   'emoji'=>'☀️','mult'=>2.0,'desc'=>'Massive sun-powered blast.','power'=>'★★★★'],
            'synthesis'   => ['name'=>'Synthesis',    'emoji'=>'💚','mult'=>0.5,'desc'=>'Heals self, low damage.','power'=>'★☆☆☆'],
        ],
        'Water'    => [
            'watergun'    => ['name'=>'Water Gun',    'emoji'=>'💦','mult'=>1.0,'desc'=>'A steady stream of water.','power'=>'★★☆☆'],
            'bubblebeam'  => ['name'=>'Bubble Beam',  'emoji'=>'🫧','mult'=>1.4,'desc'=>'Bubbles slow the enemy.','power'=>'★★★☆'],
            'hydropump'   => ['name'=>'Hydro Pump',   'emoji'=>'🌊','mult'=>2.0,'desc'=>'Enormous torrent of water.','power'=>'★★★★'],
            'aquajet'     => ['name'=>'Aqua Jet',     'emoji'=>'💨','mult'=>0.7,'desc'=>'Strikes first, foe hits less.','power'=>'★☆☆☆'],
        ],
        'Fire'     => [
            'ember'       => ['name'=>'Ember',        'emoji'=>'🔥','mult'=>1.0,'desc'=>'A small flame attack.','power'=>'★★☆☆'],
            'flamethrower'=> ['name'=>'Flamethrower', 'emoji'=>'🌋','mult'=>1.4,'desc'=>'Intense jet of fire.','power'=>'★★★☆'],
            'fireblast'   => ['name'=>'Fire Blast',   'emoji'=>'💥','mult'=>2.0,'desc'=>'A massive explosion of fire.','power'=>'★★★★'],
            'quickflame'  => ['name'=>'Quick Flame',  'emoji'=>'⚡','mult'=>0.7,'desc'=>'Fast strike, foe hits less.','power'=>'★☆☆☆'],
        ],
        'Electric' => [
            'thundershock'=> ['name'=>'Thundershock', 'emoji'=>'⚡','mult'=>1.0,'desc'=>'A jolt of electricity.','power'=>'★★☆☆'],
            'thunderbolt' => ['name'=>'Thunderbolt',  'emoji'=>'🌩️','mult'=>1.4,'desc'=>'A powerful electric strike.','power'=>'★★★☆'],
            'thunder'     => ['name'=>'Thunder',      'emoji'=>'🌪️','mult'=>2.0,'desc'=>'Massive lightning storm.','power'=>'★★★★'],
            'quickcharge' => ['name'=>'Quick Charge', 'emoji'=>'💨','mult'=>0.7,'desc'=>'Fast zap, foe hits less.','power'=>'★☆☆☆'],
        ],
        'Bug'      => [
            'bugbite'     => ['name'=>'Bug Bite',     'emoji'=>'🐛','mult'=>1.0,'desc'=>'A chomping bite attack.','power'=>'★★☆☆'],
            'xscissor'    => ['name'=>'X-Scissor',    'emoji'=>'✂️','mult'=>1.4,'desc'=>'Slashing cross-cut attack.','power'=>'★★★☆'],
            'bugbuzz'     => ['name'=>'Bug Buzz',     'emoji'=>'📳','mult'=>2.0,'desc'=>'Sonic vibration wave.','power'=>'★★★★'],
            'stringshot'  => ['name'=>'String Shot',  'emoji'=>'🕸️','mult'=>0.7,'desc'=>'Slows enemy, low damage.','power'=>'★☆☆☆'],
        ],
        'Flying'   => [
            'gust'        => ['name'=>'Gust',         'emoji'=>'🌬️','mult'=>1.0,'desc'=>'A sharp gust of wind.','power'=>'★★☆☆'],
            'airslash'    => ['name'=>'Air Slash',    'emoji'=>'🦅','mult'=>1.4,'desc'=>'Razor-sharp air blade.','power'=>'★★★☆'],
            'hurricane'   => ['name'=>'Hurricane',   'emoji'=>'🌀','mult'=>2.0,'desc'=>'A raging wind storm.','power'=>'★★★★'],
            'quickwind'   => ['name'=>'Quick Wind',   'emoji'=>'💨','mult'=>0.7,'desc'=>'Swift strike, foe hits less.','power'=>'★☆☆☆'],
        ],
        'Rock'     => [
            'rockthrow'   => ['name'=>'Rock Throw',  'emoji'=>'🪨','mult'=>1.0,'desc'=>'Hurls a sharp boulder.','power'=>'★★☆☆'],
            'rockslide'   => ['name'=>'Rock Slide',  'emoji'=>'🏔️','mult'=>1.4,'desc'=>'Raining rocks hit hard.','power'=>'★★★☆'],
            'stoneedge'   => ['name'=>'Stone Edge',  'emoji'=>'💎','mult'=>2.0,'desc'=>'Jagged stone critical strike.','power'=>'★★★★'],
            'rollout'     => ['name'=>'Rollout',     'emoji'=>'⚪','mult'=>0.7,'desc'=>'Defensive roll, foe hits less.','power'=>'★☆☆☆'],
        ],
        'Ice'      => [
            'iceshard'    => ['name'=>'Ice Shard',   'emoji'=>'🧊','mult'=>1.0,'desc'=>'Fast frozen fragment.','power'=>'★★☆☆'],
            'icebeam'     => ['name'=>'Ice Beam',    'emoji'=>'❄️','mult'=>1.4,'desc'=>'Freezing concentrated beam.','power'=>'★★★☆'],
            'blizzard'    => ['name'=>'Blizzard',    'emoji'=>'🌨️','mult'=>2.0,'desc'=>'Devastating snowstorm.','power'=>'★★★★'],
            'frostbreath' => ['name'=>'Frost Breath','emoji'=>'💨','mult'=>0.7,'desc'=>'Chilling gust, foe hits less.','power'=>'★☆☆☆'],
        ],
    ];
    $fallback = [
        'tackle'  => ['name'=>'Tackle',       'emoji'=>'⚡','mult'=>1.0,'desc'=>'A basic body slam.','power'=>'★★☆☆'],
        'slam'    => ['name'=>'Slam',          'emoji'=>'💥','mult'=>1.5,'desc'=>'Heavy blow.','power'=>'★★★☆'],
        'quick'   => ['name'=>'Quick Hit',     'emoji'=>'💨','mult'=>0.7,'desc'=>'Fast, foe hits less.','power'=>'★☆☆☆'],
        'special' => ['name'=>'Special Blast', 'emoji'=>'✨','mult'=>2.0,'desc'=>'Massive power!','power'=>'★★★★'],
    ];
    return $moves[$type] ?? $fallback;
}

function resolveMove(string $moveKey, string $type): array {
    $moves = getMovesForType($type);
    return $moves[$moveKey] ?? array_values($moves)[0];
}

// ── LOAD JSON ─────────────────────────────────────────────────────────────────
$jsonData      = file_get_contents('data/creatures.json');
$creaturesData = json_decode($jsonData, true);
$z2WildPool    = array_values(array_filter($creaturesData['creatures'], function($c) {
    return $c['category'] === 'wild_zone2';
}));

// ── ZONE TRACKING ─────────────────────────────────────────────────────────────
$_SESSION['last_map_zone'] = 2;

// ── ACTION HANDLERS ───────────────────────────────────────────────────────────

// Wild encounter starts
if (isset($_POST['wild_encounter'])) {
    $_SESSION['z2_zone'] = 'battle';
    $picked = $z2WildPool[array_rand($z2WildPool)];
    $_SESSION['wild_name']       = $picked['name'];
    $_SESSION['wild_type']       = $picked['type'];
    $_SESSION['wild_max_hp']     = $picked['hp'];
    $_SESSION['wild_hp']         = $picked['hp'];
    $_SESSION['wild_attack']     = $picked['attack'];
    $_SESSION['wild_defense']    = $picked['defense'];
    $_SESSION['wild_speed']      = $picked['speed'];
    $_SESSION['wild_battle_msg'] = "A wild {$picked['name']} appeared from the rocks! What will you do?";
    $_SESSION['wild_caught']     = false;
    $_SESSION['wild_fled']       = false;
    $_SESSION['level_up_msg']    = '';
    syncActiveFromTeam();
}

// Wild attack
if (isset($_POST['wild_attack'])) {
    $move       = $_POST['move'] ?? '';
    $activeType = $_SESSION['my_type'] ?? ($_SESSION['team'][0]['type'] ?? 'Normal');
    $chosen     = resolveMove($move, $activeType);
    $dmgToWild  = max(1, round(($_SESSION['my_attack'] / 3) * $chosen['mult']));
    $isQuick    = ($chosen['mult'] <= 0.75);
    $dmgToMe    = max(1, round(($_SESSION['wild_attack'] / 3) * ($isQuick ? 0.5 : 1.0)));
    $_SESSION['level_up_msg'] = '';
    $_SESSION['wild_hp'] -= $dmgToWild;

    if ($_SESSION['wild_hp'] <= 0) {
        $_SESSION['wild_hp'] = 0;
        $_SESSION['kills'] = ($_SESSION['kills'] ?? 0) + 1;
        $kills = $_SESSION['kills'];
        $lvMsg = awardXPToActive(15);
        $_SESSION['level_up_msg'] = $lvMsg;
        $killNote = " ($kills/5 defeated)";
        if ($kills >= 5 && !($_SESSION['trainer_unlocked'] ?? false)) {
            $_SESSION['trainer_unlocked'] = true;
            $killNote = " 🏆 5 defeated! Find Trainer Mira on the map!";
        }
        $_SESSION['wild_battle_msg'] = "You used {$chosen['emoji']} {$chosen['name']} for $dmgToWild damage! Wild {$_SESSION['wild_name']} fainted! (+15 XP){$killNote}" . ($lvMsg ? " 🎉 $lvMsg" : '');
    } else {
        $_SESSION['my_hp'] -= $dmgToMe;
        if (!empty($_SESSION['team'])) $_SESSION['team'][0]['hp'] = $_SESSION['my_hp'];
        if ($_SESSION['my_hp'] <= 0) {
            $_SESSION['my_hp'] = 0;
            $_SESSION['wild_battle_msg'] = "You dealt $dmgToWild damage but {$_SESSION['wild_name']} hit back for $dmgToMe! Your creature fainted!";
        } else {
            $_SESSION['wild_battle_msg'] = "You used {$chosen['emoji']} {$chosen['name']} for $dmgToWild damage! {$_SESSION['wild_name']} hit back for $dmgToMe!";
        }
    }
}

// Throw ball
if (isset($_POST['throw_ball'])) {
    $_SESSION['level_up_msg'] = '';
    if (($_SESSION['balls'] ?? 0) <= 0) {
        $_SESSION['wild_battle_msg'] = "No balls left!";
    } else {
        $_SESSION['balls']--;
        $hpPct      = $_SESSION['wild_hp'] / max(1, $_SESSION['wild_max_hp']);
        $catchRate  = max(0.25, 0.80 - ($hpPct * 0.55));
        if (lcg_value() < $catchRate) {
            $_SESSION['wild_caught'] = true;
            $caught = [
                'name'    => $_SESSION['wild_name'],
                'type'    => $_SESSION['wild_type'],
                'hp'      => $_SESSION['wild_hp'],
                'max_hp'  => $_SESSION['wild_max_hp'],
                'attack'  => $_SESSION['wild_attack'],
                'defense' => $_SESSION['wild_defense'],
                'speed'   => $_SESSION['wild_speed'],
                'level'   => 1, 'xp' => 0, 'xp_next' => xpForLevel(1),
            ];
            $lvMsg = awardXPToActive(5);
            $_SESSION['level_up_msg'] = $lvMsg;
            if (count($_SESSION['team'] ?? []) < 6) {
                $_SESSION['team'][] = $caught;
                $_SESSION['wild_battle_msg'] = "Gotcha! {$_SESSION['wild_name']} was caught! (+5 XP)" . ($lvMsg ? " 🎉 $lvMsg" : '');
            } else {
                $_SESSION['wild_battle_msg'] = "Team is full! {$_SESSION['wild_name']} got away.";
                $_SESSION['wild_caught'] = false;
            }
        } else {
            $_SESSION['wild_battle_msg'] = "{$_SESSION['wild_name']} broke free! {$_SESSION['balls']} balls left.";
        }
    }
}

// Run away
if (isset($_POST['run_away'])) {
    $_SESSION['z2_zone'] = 'map';
    unset($_SESSION['wild_name'], $_SESSION['wild_type'], $_SESSION['wild_hp'],
          $_SESSION['wild_max_hp'], $_SESSION['wild_attack'], $_SESSION['wild_defense'],
          $_SESSION['wild_speed'], $_SESSION['wild_battle_msg'], $_SESSION['wild_caught'], $_SESSION['wild_fled']);
}

// Leave after win/catch
if (isset($_POST['leave_battle'])) {
    $_SESSION['z2_zone'] = 'map';
    unset($_SESSION['wild_name'], $_SESSION['wild_type'], $_SESSION['wild_hp'],
          $_SESSION['wild_max_hp'], $_SESSION['wild_attack'], $_SESSION['wild_defense'],
          $_SESSION['wild_speed'], $_SESSION['wild_battle_msg'], $_SESSION['wild_caught'], $_SESSION['wild_fled']);
}

// Switch creature during wild battle
if (isset($_POST['switch_creature']) && isset($_POST['switch_idx'])) {
    $idx = (int)$_POST['switch_idx'];
    if (isset($_SESSION['team'][$idx]) && $idx !== 0 && $_SESSION['team'][$idx]['hp'] > 0) {
        $tmp = $_SESSION['team'][0];
        $_SESSION['team'][0] = $_SESSION['team'][$idx];
        $_SESSION['team'][$idx] = $tmp;
        syncActiveFromTeam();
        $switched = $_SESSION['team'][0]['name'];
        $freeDmg  = max(1, round(($_SESSION['wild_attack'] ?? 10) / 3));
        $_SESSION['my_hp'] = max(0, ($_SESSION['my_hp'] ?? 0) - $freeDmg);
        if (!empty($_SESSION['team'])) $_SESSION['team'][0]['hp'] = $_SESSION['my_hp'];
        $_SESSION['wild_battle_msg'] = "You switched to $switched! Enemy hit for $freeDmg while you swapped!";
    }
}

// Trainer encounter
if (isset($_POST['trainer_encounter'])) {
    if (!($_SESSION['trainer_unlocked'] ?? false)) {
        $_SESSION['z2_zone'] = 'map';
    } else {
        $_SESSION['z2_zone']             = 'trainer_battle';
        $_SESSION['trainer_name']        = 'Trainer Mira';
        $_SESSION['trainer_creature']    = 'Frostclaw';
        $_SESSION['trainer_emoji']       = '❄️';
        $_SESSION['trainer_hp']          = 130;
        $_SESSION['trainer_max_hp']      = 130;
        $_SESSION['trainer_attack']      = 95;
        $_SESSION['trainer_battle_msg']  = "Trainer Mira sends out Frostclaw! What will you do?";
        $_SESSION['trainer_defeated']    = false;
        $_SESSION['level_up_msg']        = '';
        syncActiveFromTeam();
    }
}

// Trainer attack
if (isset($_POST['trainer_attack'])) {
    $move       = $_POST['move'] ?? '';
    $activeType = $_SESSION['my_type'] ?? ($_SESSION['team'][0]['type'] ?? 'Normal');
    $chosen     = resolveMove($move, $activeType);
    $dmgToTr    = max(1, round(($_SESSION['my_attack'] / 3) * $chosen['mult']));
    $isQuick    = ($chosen['mult'] <= 0.75);
    $dmgToMe    = max(1, round(($_SESSION['trainer_attack'] / 3) * ($isQuick ? 0.5 : 1.0)));
    $_SESSION['level_up_msg'] = '';
    $_SESSION['trainer_hp'] -= $dmgToTr;

    if ($_SESSION['trainer_hp'] <= 0) {
        $_SESSION['trainer_hp']       = 0;
        $_SESSION['trainer_defeated'] = true;
        $lvMsg = awardXPToActive(50);
        $_SESSION['level_up_msg'] = $lvMsg;
        $_SESSION['trainer_battle_msg'] = "You dealt $dmgToTr damage! Frostclaw fainted! You beat Trainer Mira! (+50 XP)" . ($lvMsg ? " 🎉 $lvMsg" : '');
    } else {
        $_SESSION['my_hp'] -= $dmgToMe;
        if (!empty($_SESSION['team'])) $_SESSION['team'][0]['hp'] = $_SESSION['my_hp'];
        if ($_SESSION['my_hp'] <= 0) {
            $_SESSION['my_hp'] = 0;
            $_SESSION['trainer_battle_msg'] = "You dealt $dmgToTr damage but Frostclaw hit back for $dmgToMe! Your creature fainted!";
        } else {
            $_SESSION['trainer_battle_msg'] = "You used {$chosen['emoji']} {$chosen['name']} for $dmgToTr damage! Frostclaw hit back for $dmgToMe!";
        }
    }
}

// Trainer switch
if (isset($_POST['trainer_switch_creature']) && isset($_POST['switch_idx'])) {
    $idx = (int)$_POST['switch_idx'];
    if (isset($_SESSION['team'][$idx]) && $idx !== 0 && $_SESSION['team'][$idx]['hp'] > 0) {
        $tmp = $_SESSION['team'][0];
        $_SESSION['team'][0] = $_SESSION['team'][$idx];
        $_SESSION['team'][$idx] = $tmp;
        syncActiveFromTeam();
        $switched = $_SESSION['team'][0]['name'];
        $freeDmg  = max(1, round(($_SESSION['trainer_attack'] ?? 10) / 3));
        $_SESSION['my_hp'] = max(0, ($_SESSION['my_hp'] ?? 0) - $freeDmg);
        if (!empty($_SESSION['team'])) $_SESSION['team'][0]['hp'] = $_SESSION['my_hp'];
        $_SESSION['trainer_battle_msg'] = "You switched to $switched! Mira hit for $freeDmg while you swapped!";
    }
}

// Advance to zone 3
if (isset($_POST['advance_zone'])) {
    $_SESSION['zone']             = 3;
    $_SESSION['last_map_zone']    = 3;
    $_SESSION['kills']            = 0;
    $_SESSION['trainer_unlocked'] = false;
    $_SESSION['balls']            = ($_SESSION['balls'] ?? 0) + 5;
    unset($_SESSION['trainer_hp'], $_SESSION['trainer_max_hp'], $_SESSION['trainer_attack'],
          $_SESSION['trainer_battle_msg'], $_SESSION['trainer_defeated'], $_SESSION['trainer_name'],
          $_SESSION['trainer_creature'], $_SESSION['trainer_emoji'], $_SESSION['z2_zone']);
    header('Location: game.php'); exit;
}

// Open/close team screen
if (isset($_POST['open_team'])) {
    $_SESSION['z2_prev'] = $_SESSION['z2_zone'] ?? 'map';
    $_SESSION['z2_zone'] = 'team';
}
if (isset($_POST['close_team'])) {
    $_SESSION['z2_zone'] = $_SESSION['z2_prev'] ?? 'map';
}

// Default zone state
if (!isset($_SESSION['z2_zone'])) $_SESSION['z2_zone'] = 'map';

// ── DISPLAY HELPERS ───────────────────────────────────────────────────────────
$zone       = $_SESSION['z2_zone'];
$kills      = $_SESSION['kills'] ?? 0;
$balls      = $_SESSION['balls'] ?? 0;
$teamCount  = count($_SESSION['team'] ?? []);
$trUnlocked = (bool)($_SESSION['trainer_unlocked'] ?? false);

$typeEmoji = [
    'Bug'=>'🐛','Flying'=>'🦅','Fire'=>'🔥','Water'=>'💧','Grass'=>'🌿',
    'Electric'=>'⚡','Normal'=>'🐾','Rock'=>'🪨','Ghost'=>'👻','Psychic'=>'🔮',
    'Ice'=>'🧊','Dragon'=>'🐉','Dark'=>'🌑','Steel'=>'⚙️'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Pokequest – Zone 2: Rocky Peaks</title>
  <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    :root {
      --bg:      #0f1729;
      --surface: rgba(255,255,255,0.04);
      --border:  rgba(147,197,253,0.12);
      --text:    #e0e8f0;
      --muted:   #5a7aaa;
      --accent:  #1d6fa4;
      --accent2: #93c5fd;
      --gold:    #fbbf24;
      --green:   #22c55e;
      --red:     #ef4444;
      --ice:     #a0d4f0;
    }
    body {
      background: var(--bg);
      color: var(--text);
      font-family: 'Nunito', sans-serif;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      align-items: center;
      padding: 16px 12px 40px;
    }

    /* ── HUD ── */
    #hud {
      width: 100%; max-width: 520px;
      display: flex; align-items: center; justify-content: space-between;
      background: rgba(29,111,164,0.12);
      border: 1px solid var(--border);
      border-radius: 14px;
      padding: 10px 16px;
      margin-bottom: 14px;
      font-size: 13px; font-weight: 700;
      gap: 10px; flex-wrap: wrap;
    }
    #hud .hud-name { font-family:'Press Start 2P',monospace; font-size:8px; color:var(--accent2); }
    #hud .hud-right { display:flex; align-items:center; gap:10px; }

    /* ── OBJECTIVE BAR ── */
    #objective-bar {
      width:100%; max-width:520px;
      background: rgba(255,255,255,0.04);
      border:1px solid var(--border);
      border-radius:12px; padding:10px 14px;
      margin-bottom:14px; font-size:12px;
    }
    .obj-label { color:var(--muted); margin-bottom:6px; }
    .obj-track { background:rgba(255,255,255,0.07); border-radius:999px; height:8px; overflow:hidden; }
    .obj-fill  { height:100%; border-radius:999px; transition:width .4s; }

    /* ── CARD WRAPPER ── */
    #card {
      width:100%; max-width:520px;
      background: rgba(255,255,255,0.03);
      border:1px solid var(--border);
      border-radius:20px;
      padding:20px 18px;
    }
    h2 {
      font-family:'Press Start 2P',monospace;
      font-size:11px; color:var(--accent2);
      margin-bottom:16px; text-align:center;
      text-shadow: 0 0 20px rgba(147,197,253,0.4);
    }

    /* ── BUTTONS ── */
    .btn {
      display:block; width:100%;
      padding:12px; border-radius:12px;
      border:none; cursor:pointer;
      font-family:'Nunito',sans-serif; font-weight:800; font-size:14px;
      transition:all .15s;
    }
    .btn:hover { transform:translateY(-1px); filter:brightness(1.1); }
    .btn-primary { background:linear-gradient(135deg,#1d6fa4,#0ea5e9); color:#fff; }
    .btn-danger  { background:linear-gradient(135deg,#dc2626,#ef4444); color:#fff; }
    .btn-success { background:linear-gradient(135deg,#15803d,#22c55e); color:#fff; }
    .btn-muted   { background:rgba(255,255,255,0.07); color:var(--muted); border:1px solid var(--border); }
    .btn + .btn  { margin-top:8px; }

    /* ── BATTLE ARENA ── */
    .battle-msg {
      background:rgba(147,197,253,0.06);
      border-left:3px solid var(--accent2);
      border-radius:10px; padding:12px 14px;
      font-size:13px; line-height:1.6;
      margin-bottom:14px;
    }
    .wild-arena {
      display:flex; align-items:center; justify-content:space-between;
      gap:10px; margin-bottom:16px;
    }
    .wild-card {
      flex:1; text-align:center;
      background:rgba(255,255,255,0.04);
      border:1.5px solid var(--border);
      border-radius:14px; padding:14px 10px;
    }
    .wild-card.mine  { border-color:rgba(34,197,94,0.3); background:rgba(34,197,94,0.05); }
    .wild-card.enemy { border-color:rgba(147,197,253,0.3); background:rgba(29,111,164,0.07); }
    .wild-sprite { font-size:36px; display:block; margin-bottom:6px; }
    .wild-card h3 { font-size:12px; font-weight:800; margin-bottom:4px; }
    .hp-text  { font-size:11px; color:var(--muted); margin-bottom:4px; }
    .hp-track { background:rgba(255,255,255,0.08); border-radius:999px; height:7px; overflow:hidden; }
    .hp-fill  { height:100%; border-radius:999px; transition:width .5s; }
    .vs-badge {
      font-family:'Press Start 2P',monospace; font-size:10px;
      color:var(--gold); flex-shrink:0;
    }
    .type-badge { font-size:10px; color:var(--muted); display:block; margin-top:4px; }

    /* ── MOVE OVERLAY ── */
    .move-overlay {
      display:none; position:fixed; inset:0;
      background:rgba(0,0,0,0.75); backdrop-filter:blur(4px);
      z-index:200; align-items:flex-end; justify-content:center; padding:20px;
    }
    .move-overlay.open { display:flex; }
    .move-panel {
      background:#0f1729; border:1.5px solid var(--border);
      border-radius:20px 20px 0 0; padding:20px; width:100%; max-width:480px;
    }
    .move-panel h4 {
      font-family:'Press Start 2P',monospace; font-size:9px;
      color:var(--accent2); margin-bottom:14px; text-align:center;
    }
    .move-btn {
      width:100%; background:rgba(255,255,255,0.05);
      border:1.5px solid var(--border); border-radius:12px;
      padding:11px 14px; cursor:pointer; text-align:left;
      color:var(--text); font-family:'Nunito',sans-serif; font-size:13px;
      display:flex; align-items:center; gap:10px; margin-bottom:8px;
      transition:all .15s;
    }
    .move-btn:hover { background:rgba(147,197,253,0.1); border-color:var(--accent2); }
    .move-btn .move-icon { font-size:22px; }
    .move-btn .move-info { flex:1; }
    .move-btn .move-name { font-weight:800; font-size:13px; }
    .move-btn .move-desc { font-size:11px; color:var(--muted); }
    .move-btn .move-power{ font-size:11px; color:var(--gold); }
    .cancel-btn {
      width:100%; background:rgba(255,255,255,0.06);
      border:1px solid var(--border); border-radius:10px;
      padding:10px; color:var(--muted); font-weight:700;
      cursor:pointer; font-family:'Nunito',sans-serif; font-size:13px;
      margin-top:4px;
    }

    /* ── SWITCH OVERLAY ── */
    .switch-overlay {
      display:none; position:fixed; inset:0;
      background:rgba(0,0,0,0.8); backdrop-filter:blur(4px);
      z-index:210; align-items:flex-end; justify-content:center; padding:20px;
    }
    .switch-overlay.open { display:flex; }
    .switch-panel {
      background:#0f1729; border:1.5px solid var(--border);
      border-radius:20px 20px 0 0; padding:20px; width:100%; max-width:480px;
    }
    .switch-panel h4 {
      font-family:'Press Start 2P',monospace; font-size:9px;
      color:var(--accent2); margin-bottom:6px; text-align:center;
    }
    .switch-warning { font-size:11px; color:#f59e0b; text-align:center; margin-bottom:12px; }

    /* ── BALLS ROW ── */
    .balls-row {
      display:flex; align-items:center; justify-content:space-between;
      gap:8px; margin-bottom:12px; flex-wrap:wrap;
    }
    .balls-count {
      background:rgba(251,191,36,0.1); border:1px solid rgba(251,191,36,0.25);
      border-radius:8px; padding:6px 12px;
      font-size:12px; font-weight:800; color:var(--gold);
    }

    /* ── TEAM SCREEN ── */
    .team-card-full {
      background:rgba(255,255,255,0.04);
      border:1.5px solid var(--border);
      border-radius:14px; padding:14px;
      margin-bottom:10px;
    }
    .stat-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:6px; margin-top:10px; }
    .stat-box {
      background:rgba(255,255,255,0.05); border-radius:8px; padding:6px 4px;
      text-align:center;
    }
    .stat-val { display:block; font-weight:800; font-size:14px; }
    .stat-lbl { display:block; font-size:9px; color:var(--muted); }

    /* ── BATTLE FLASH ── */
    #battle-flash {
      position:fixed; inset:0; background:#fff;
      pointer-events:none; opacity:0; z-index:500;
    }

    /* ── ENCOUNTER POPUP ── */
    #encounter-popup {
      display:none; position:fixed; inset:0;
      background:rgba(0,0,0,0.75); backdrop-filter:blur(6px);
      z-index:400; align-items:center; justify-content:center;
    }
    #encounter-popup.show { display:flex; }
    #encounter-box {
      background:#0f1729; border:2px solid var(--accent2);
      border-radius:20px; padding:28px 24px; max-width:340px; width:90%;
      text-align:center; box-shadow:0 0 40px rgba(147,197,253,0.3);
    }
    #encounter-box .mob-sprite { font-size:52px; display:block; margin-bottom:10px; }
    #encounter-box h3 { font-size:15px; font-weight:800; color:var(--accent2); margin-bottom:4px; }

    /* ── DIALOGUE BOX ── */
    .dialogue-box {
      background:rgba(255,255,255,0.04);
      border-left:3px solid var(--accent);
      border-radius:10px; padding:12px 14px;
      font-size:13px; line-height:1.6;
      margin-bottom:14px; color:var(--muted);
    }

    /* ── MAP ── */
    #zone2-wrap { display:flex; flex-direction:column; align-items:center; gap:10px; }
    #zone2Canvas {
      border:3px solid rgba(147,197,253,0.2);
      border-radius:12px; image-rendering:pixelated;
      box-shadow:0 0 40px rgba(100,180,255,0.15); display:block;
    }
    #zone2-hud-bar {
      width:480px; display:flex; justify-content:space-between;
      font-size:12px; color:var(--muted);
    }
    #zone2-hud-bar strong { color:var(--accent2); }
    #z2-hint { font-size:11px; color:var(--muted); text-align:center; opacity:.7; }
  </style>
</head>
<body>

<!-- ══ GLOBAL HUD ════════════════════════════════════════════════════════════ -->
<div id="hud">
  <div>
    <div class="hud-name">🏔️ <?php echo htmlspecialchars($_SESSION['playerName'] ?? 'Trainer'); ?></div>
    <div style="font-size:12px;color:var(--muted);margin-top:3px;">Zone 2: Rocky Peaks</div>
  </div>
  <div class="hud-right">
    <span class="balls-count">🟡 <?php echo $balls; ?> balls</span>
    <?php if ($teamCount > 0): ?>
    <form action="zone2.php" method="POST" style="margin:0;">
      <input type="hidden" name="open_team" value="1">
      <button class="btn btn-muted" type="submit" style="padding:6px 12px;font-size:12px;width:auto;">
        🎒 Team (<?php echo $teamCount; ?>)
      </button>
    </form>
    <?php endif; ?>
    <a href="game.php" style="font-size:11px;color:var(--muted);text-decoration:none;">← Back to Z1</a>
  </div>
</div>

<!-- ══ OBJECTIVE BAR ══════════════════════════════════════════════════════════ -->
<?php if ($zone === 'map' || $zone === 'battle' || $zone === 'trainer_battle'): ?>
<div id="objective-bar">
  <?php if ($trUnlocked): ?>
    <div class="obj-label" style="color:#f59e0b;font-weight:800;">⚠️ Find Trainer Mira on the map!</div>
    <div class="obj-track"><div class="obj-fill" style="width:100%;background:#f59e0b;"></div></div>
  <?php else: ?>
    <div class="obj-label">🎯 Defeat <?php echo max(0, 5 - $kills); ?> more wild creatures (<?php echo $kills; ?>/5)</div>
    <div class="obj-track">
      <div class="obj-fill" style="width:<?php echo min(100, $kills * 20); ?>%;background:<?php echo $kills >= 5 ? '#f59e0b' : '#22c55e'; ?>;"></div>
    </div>
  <?php endif; ?>
</div>
<?php endif; ?>

<!-- ══ MAIN CARD ══════════════════════════════════════════════════════════════ -->
<div id="card">

<?php
// ──────────────────────────────────────────────────────────────────────────────
// MAP
// ──────────────────────────────────────────────────────────────────────────────
if ($zone === 'map'):
?>
  <div id="zone2-wrap">
    <div id="zone2-hud-bar">
      <span>🏔️ <strong>Zone 2: Rocky Peaks</strong></span>
      <span>Arrow keys / WASD to move</span>
    </div>
    <canvas id="zone2Canvas" width="480" height="480"></canvas>
    <div id="z2-hint">
      Walk into cracked rock 🪨 to find wild creatures!
      <?php if ($trUnlocked): ?>
        <span style="color:#f59e0b;font-weight:800;">⚔️ Find Trainer Mira!</span>
      <?php endif; ?>
    </div>
  </div>

  <div id="battle-flash"></div>

  <!-- Trainer form -->
  <form id="z2-trainer-form" action="zone2.php" method="POST" style="display:none;">
    <input type="hidden" name="trainer_encounter" value="true">
  </form>

  <!-- Encounter popup -->
  <div id="encounter-popup">
    <div id="encounter-box">
      <span class="mob-sprite" id="mob-emoji">❓</span>
      <h3 id="encounter-name">Wild Creature!</h3>
      <p id="encounter-type" style="font-size:11px;color:var(--accent2);font-weight:700;text-transform:uppercase;margin-bottom:6px;"></p>
      <p id="encounter-text" style="font-size:13px;color:var(--muted);margin-bottom:10px;"></p>
      <div id="encounter-stats" style="display:flex;gap:14px;justify-content:center;margin-bottom:20px;font-size:12px;font-weight:700;color:var(--muted);"></div>
      <form action="zone2.php" method="POST">
        <input type="hidden" name="wild_encounter" value="true">
        <button class="btn btn-danger" type="submit">⚔️ Battle!</button>
      </form>
    </div>
  </div>

  <script>
  (function() {
    const TILE = 32, COLS = 15, ROWS = 15;
    const canvas = document.getElementById('zone2Canvas');
    const ctx    = canvas.getContext('2d');

    const T = { PATH:0, SNOW:1, CRACK:2, BOULDER:3, ICE:4, LAVA:5, CRYSTAL:6, TRAINER:7 };
    const TRAINER_X = 7, TRAINER_Y = 1;
    const trainerUnlocked = <?php echo json_encode($trUnlocked); ?>;

    const MAP = [
      [3,3,3,3,3,3,3,3,3,3,3,3,3,3,3],
      [3,0,0,0,0,0,0,7,0,0,0,0,0,0,3],
      [3,0,2,2,1,1,1,1,1,1,1,2,2,0,3],
      [3,0,2,3,2,6,1,1,1,6,2,3,2,0,3],
      [3,0,2,2,1,1,1,1,1,1,1,2,2,0,3],
      [3,0,1,1,1,0,0,5,0,0,1,1,1,0,3],
      [3,0,1,6,1,0,4,4,4,0,1,6,1,0,3],
      [3,0,1,1,1,0,4,4,4,0,1,1,1,0,3],
      [3,0,1,6,1,0,4,4,4,0,1,6,1,0,3],
      [3,0,1,1,1,0,0,5,0,0,1,1,1,0,3],
      [3,0,2,2,1,1,1,1,1,1,1,2,2,0,3],
      [3,0,2,3,2,6,1,1,1,6,2,3,2,0,3],
      [3,0,2,2,1,1,1,1,1,1,1,2,2,0,3],
      [3,0,0,0,0,0,0,0,0,0,0,0,0,0,3],
      [3,3,3,3,3,3,3,3,3,3,3,3,3,3,3],
    ];

    const WILD_CREATURES = <?php echo json_encode($z2WildPool); ?>;
    const TYPE_EMOJI = {
      'Bug':'🐛','Flying':'🦅','Fire':'🔥','Water':'💧','Grass':'🌿',
      'Electric':'⚡','Normal':'🐾','Rock':'🪨','Ghost':'👻','Psychic':'🔮',
      'Ice':'🧊','Dragon':'🐉','Dark':'🌑','Steel':'⚙️'
    };

    let px = 7, py = 7, facing = 'down', stepFrame = 0, moving = false;
    let lastMoveTime = 0;
    const MOVE_DELAY = 150;
    const keys = {};
    let inEncounter = false;

    function isWalkable(x, y) {
      if (x < 0 || y < 0 || x >= COLS || y >= ROWS) return false;
      const t = MAP[y][x];
      return t !== T.BOULDER && t !== T.ICE;
    }
    function isCrack(x, y) { return MAP[y][x] === T.CRACK; }

    function drawTile(tx, ty) {
      const t = MAP[ty][tx];
      const x = tx * TILE, y = ty * TILE;
      const baseColors = {
        0:'#5a5a6e', 1:'#c8d8e8', 2:'#6b5a4e', 3:'#3a3a4a',
        4:'#a0d4f0', 5:'#3a2a1a', 6:'#c8d8e8', 7:'#5a5a6e'
      };
      ctx.fillStyle = baseColors[t] || '#555';
      ctx.fillRect(x, y, TILE, TILE);

      if (t === T.PATH) {
        ctx.strokeStyle = 'rgba(0,0,0,0.18)'; ctx.lineWidth = 1;
        ctx.strokeRect(x+2, y+2, TILE-4, TILE-4);
      }
      if (t === T.SNOW) {
        ctx.fillStyle = 'rgba(255,255,255,0.5)';
        [[x+6,y+6],[x+18,y+14],[x+26,y+8],[x+10,y+22]].forEach(function(p) {
          ctx.fillRect(p[0], p[1], 2, 2);
        });
      }
      if (t === T.CRACK) {
        ctx.strokeStyle = '#b8860b'; ctx.lineWidth = 1.5; ctx.beginPath();
        ctx.moveTo(x+8, y+6);   ctx.lineTo(x+16, y+14);
        ctx.moveTo(x+16, y+14); ctx.lineTo(x+12, y+24);
        ctx.moveTo(x+16, y+14); ctx.lineTo(x+24, y+20);
        ctx.stroke();
      }
      if (t === T.BOULDER) {
        ctx.fillStyle = '#2a2a36'; ctx.fillRect(x+2, y+8, TILE-4, TILE-10);
        ctx.fillStyle = '#4a4a5e'; ctx.beginPath();
        ctx.arc(x+16, y+10, 9, 0, Math.PI); ctx.fill();
      }
      if (t === T.ICE) {
        var tw = Date.now() / 1200;
        ctx.fillStyle = 'rgba(180,230,255,' + (0.3 + Math.sin(tw + tx + ty)*0.2) + ')';
        ctx.fillRect(x+2, y+2, TILE-4, TILE-4);
        ctx.strokeStyle = 'rgba(255,255,255,0.4)'; ctx.lineWidth = 1;
        ctx.beginPath(); ctx.moveTo(x+4, y+10); ctx.lineTo(x+12, y+6); ctx.stroke();
      }
      if (t === T.LAVA) {
        var tl = Date.now() / 500;
        var glow = 0.4 + Math.sin(tl + tx*0.7)*0.3;
        ctx.fillStyle = 'rgba(255,80,0,' + glow + ')';
        ctx.fillRect(x+6, y+6, TILE-12, TILE-12);
        ctx.fillStyle = 'rgba(255,180,0,' + (glow*0.6) + ')';
        ctx.fillRect(x+10, y+10, TILE-20, TILE-20);
      }
      if (t === T.CRYSTAL) {
        ctx.fillStyle = '#4dd9d9'; ctx.beginPath();
        ctx.moveTo(x+16,y+4); ctx.lineTo(x+22,y+14);
        ctx.lineTo(x+16,y+28); ctx.lineTo(x+10,y+14);
        ctx.closePath(); ctx.fill();
        ctx.fillStyle = 'rgba(255,255,255,0.4)'; ctx.beginPath();
        ctx.moveTo(x+16,y+4); ctx.lineTo(x+19,y+14); ctx.lineTo(x+16,y+10);
        ctx.closePath(); ctx.fill();
      }
    }

    function drawTrainer() {
      var tx = TRAINER_X * TILE, ty = TRAINER_Y * TILE;
      var pulse = Math.sin(Date.now() / 400) * 0.5 + 0.5;
      if (trainerUnlocked) {
        ctx.save();
        ctx.shadowColor = '#93c5fd';
        ctx.shadowBlur  = 14 + pulse * 10;
        ctx.fillStyle   = 'rgba(147,197,253,0.2)';
        ctx.beginPath(); ctx.arc(tx+16, ty+16, 18, 0, Math.PI*2); ctx.fill();
        ctx.restore();
      }
      ctx.fillStyle = trainerUnlocked ? '#1d6fa4' : '#4b5563';
      ctx.fillRect(tx+9, ty+16, 14, 12);
      ctx.fillStyle = '#fcd5b0'; ctx.beginPath();
      ctx.arc(tx+16, ty+12, 8, 0, Math.PI*2); ctx.fill();
      ctx.fillStyle = '#e0e8f0'; ctx.fillRect(tx+9, ty+5, 14, 7);
      ctx.fillStyle = '#1e293b';
      ctx.fillRect(tx+13, ty+11, 2, 2); ctx.fillRect(tx+17, ty+11, 2, 2);
      ctx.fillStyle = '#1e3a5f';
      ctx.fillRect(tx+10, ty+28, 5, 5); ctx.fillRect(tx+17, ty+28, 5, 5);
      ctx.font = 'bold 9px sans-serif'; ctx.textAlign = 'center';
      ctx.fillStyle = trainerUnlocked ? '#93c5fd' : 'rgba(255,255,255,0.35)';
      ctx.fillText(trainerUnlocked ? '⚔ MIRA' : 'MIRA', tx+16, ty-4);
      ctx.textAlign = 'left';
    }

    function drawPlayer() {
      var x = px * TILE, y = py * TILE;
      var bob = moving ? (stepFrame === 1 ? -1 : 0) : 0;
      ctx.fillStyle = 'rgba(0,0,0,0.25)'; ctx.beginPath();
      ctx.ellipse(x+16, y+33, 8, 3, 0, 0, Math.PI*2); ctx.fill();
      ctx.fillStyle = '#0f172a'; ctx.fillRect(x+9, y+16+bob, 14, 12);
      ctx.fillStyle = '#fcd5b0'; ctx.beginPath();
      ctx.arc(x+16, y+12+bob, 8, 0, Math.PI*2); ctx.fill();
      ctx.fillStyle = '#92400e';
      if      (facing==='down')  { ctx.fillRect(x+9,y+6+bob,14,6); ctx.fillRect(x+13,y+11+bob,2,2); ctx.fillRect(x+17,y+11+bob,2,2); }
      else if (facing==='up')    { ctx.fillRect(x+9,y+6+bob,14,6); }
      else if (facing==='left')  { ctx.fillRect(x+11,y+11+bob,2,2); }
      else if (facing==='right') { ctx.fillRect(x+19,y+11+bob,2,2); }
      ctx.fillStyle = '#1d4ed8';
      ctx.fillRect(x+8, y+7+bob, 16, 4); ctx.fillRect(x+10, y+4+bob, 12, 4);
      ctx.fillStyle = '#1e3a5f';
      var legOff = moving && stepFrame === 1 ? 2 : 0;
      ctx.fillRect(x+10, y+28+bob, 5, 6+legOff);
      ctx.fillRect(x+17, y+28+bob, 5, 6-legOff);
    }

    function draw() {
      ctx.clearRect(0, 0, canvas.width, canvas.height);
      for (var ry = 0; ry < ROWS; ry++)
        for (var rx = 0; rx < COLS; rx++)
          drawTile(rx, ry);
      drawTrainer();
      drawPlayer();
    }

    document.addEventListener('keydown', function(e) { keys[e.key] = true; });
    document.addEventListener('keyup',   function(e) { keys[e.key] = false; });

    function tryMove() {
      if (inEncounter) return;
      var now = Date.now();
      if (now - lastMoveTime < MOVE_DELAY) return;
      var nx = px, ny = py, moved = false;
      if      (keys['ArrowUp']    || keys['w'] || keys['W']) { ny--; facing='up';    moved=true; }
      else if (keys['ArrowDown']  || keys['s'] || keys['S']) { ny++; facing='down';  moved=true; }
      else if (keys['ArrowLeft']  || keys['a'] || keys['A']) { nx--; facing='left';  moved=true; }
      else if (keys['ArrowRight'] || keys['d'] || keys['D']) { nx++; facing='right'; moved=true; }

      if (moved && isWalkable(nx, ny)) {
        if (nx === TRAINER_X && ny === TRAINER_Y) {
          if (trainerUnlocked) { inEncounter = true; document.getElementById('z2-trainer-form').submit(); }
          return;
        }
        px = nx; py = ny;
        stepFrame = 1 - stepFrame;
        lastMoveTime = now;
        moving = true;
        if (isCrack(px, py) && Math.random() < 0.3) triggerEncounter();
      } else if (moved) { moving = false; }
    }

    function triggerEncounter() {
      inEncounter = true;
      var flash = document.getElementById('battle-flash');
      var flashes = 0;
      var iv = setInterval(function() {
        flash.style.opacity = flashes % 2 === 0 ? '0.85' : '0';
        if (++flashes >= 6) { clearInterval(iv); flash.style.opacity = '0'; showEncounterPopup(); }
      }, 120);
    }

    function showEncounterPopup() {
      if (!WILD_CREATURES.length) return;
      var c = WILD_CREATURES[Math.floor(Math.random() * WILD_CREATURES.length)];
      var emoji = TYPE_EMOJI[c.type] || '❓';
      document.getElementById('mob-emoji').textContent = emoji;
      document.getElementById('encounter-name').textContent = 'Wild ' + c.name + '!';
      document.getElementById('encounter-type').textContent = c.type + ' Type';
      document.getElementById('encounter-text').textContent = 'A wild ' + c.name + ' emerged from the rocks!';
      document.getElementById('encounter-stats').innerHTML =
        '<span>❤️ ' + c.hp + '</span><span>⚔️ ' + c.attack + '</span>' +
        '<span>🛡️ ' + c.defense + '</span><span>💨 ' + c.speed + '</span>';
      document.getElementById('encounter-popup').classList.add('show');
    }

    window.addEventListener('keydown', function(e) {
      if (['ArrowUp','ArrowDown','ArrowLeft','ArrowRight'].indexOf(e.key) !== -1) e.preventDefault();
    });

    function gameLoop() { tryMove(); draw(); requestAnimationFrame(gameLoop); }
    gameLoop();
  })();
  </script>

<?php
// ──────────────────────────────────────────────────────────────────────────────
// WILD BATTLE
// ──────────────────────────────────────────────────────────────────────────────
elseif ($zone === 'battle'):
  $wildName    = $_SESSION['wild_name']    ?? 'Unknown';
  $wildType    = $_SESSION['wild_type']    ?? 'Normal';
  $wildHp      = $_SESSION['wild_hp']      ?? 0;
  $wildMaxHp   = $_SESSION['wild_max_hp']  ?? 1;
  $wildCaught  = $_SESSION['wild_caught']  ?? false;
  $battleMsg   = $_SESSION['wild_battle_msg'] ?? '';
  $lvMsg       = $_SESSION['level_up_msg'] ?? '';

  $myHp     = $_SESSION['my_hp']     ?? 0;
  $myMaxHp  = $_SESSION['my_max_hp'] ?? 1;
  $myAttack = $_SESSION['my_attack'] ?? 10;
  $myType   = $_SESSION['my_type']   ?? ($_SESSION['team'][0]['type'] ?? 'Normal');
  $myName   = $_SESSION['team'][0]['name'] ?? 'Your Creature';
  $myEmoji  = $typeEmoji[$myType] ?? '🐾';

  $wildEmoji   = $typeEmoji[$wildType] ?? '❓';
  $myHpPct     = max(0, round($myHp / max(1,$myMaxHp) * 100));
  $wildHpPct   = max(0, round($wildHp / max(1,$wildMaxHp) * 100));
  $myBarColor  = $myHpPct   > 50 ? '#22c55e' : ($myHpPct   > 25 ? '#f59e0b' : '#ef4444');
  $wildBarColor= $wildHpPct > 50 ? '#22c55e' : ($wildHpPct > 25 ? '#f59e0b' : '#ef4444');
  $myFainted   = $myHp <= 0;
  $wildFainted = $wildHp <= 0;

  $moves = getMovesForType($myType);
?>
  <h2>⚔️ Wild Battle!</h2>
  <div class="battle-msg">
    <?php echo htmlspecialchars($battleMsg); ?>
    <?php if ($lvMsg): ?><br><span style="color:var(--gold);">🎉 <?php echo htmlspecialchars($lvMsg); ?></span><?php endif; ?>
  </div>

  <div class="wild-arena">
    <div class="wild-card mine">
      <span class="wild-sprite"><?php echo $myEmoji; ?></span>
      <h3><?php echo htmlspecialchars($myName); ?></h3>
      <div class="hp-text"><?php echo $myHp; ?> / <?php echo $myMaxHp; ?> HP</div>
      <div class="hp-track"><div class="hp-fill" style="width:<?php echo $myHpPct; ?>%;background:<?php echo $myBarColor; ?>;"></div></div>
      <span class="type-badge"><?php echo $myType; ?> · Lv.<?php echo $_SESSION['team'][0]['level'] ?? 1; ?></span>
    </div>
    <div class="vs-badge">VS</div>
    <div class="wild-card enemy">
      <span class="wild-sprite"><?php echo $wildEmoji; ?></span>
      <h3><?php echo htmlspecialchars($wildName); ?></h3>
      <div class="hp-text"><?php echo $wildHp; ?> / <?php echo $wildMaxHp; ?> HP</div>
      <div class="hp-track"><div class="hp-fill" style="width:<?php echo $wildHpPct; ?>%;background:<?php echo $wildBarColor; ?>;"></div></div>
      <span class="type-badge"><?php echo $wildType; ?> Type</span>
    </div>
  </div>

  <?php if ($wildFainted || $wildCaught): ?>
    <!-- Battle over -->
    <div style="text-align:center;padding:10px 0 16px;">
      <div style="font-size:40px;margin-bottom:8px;"><?php echo $wildCaught ? '🎉' : '💀'; ?></div>
      <p style="font-weight:800;font-size:15px;color:<?php echo $wildCaught ? 'var(--green)' : 'var(--gold)'; ?>">
        <?php echo $wildCaught ? "You caught {$wildName}!" : "{$wildName} fainted!"; ?>
      </p>
    </div>
    <form action="zone2.php" method="POST">
      <input type="hidden" name="leave_battle" value="1">
      <button class="btn btn-primary" type="submit">← Back to Map</button>
    </form>

  <?php elseif ($myFainted): ?>
    <div style="text-align:center;padding:10px 0 16px;">
      <div style="font-size:40px;margin-bottom:8px;">💔</div>
      <p style="font-weight:800;font-size:15px;color:var(--red);">Your creature fainted!</p>
    </div>
    <form action="zone2.php" method="POST">
      <input type="hidden" name="run_away" value="1">
      <button class="btn btn-muted" type="submit">← Retreat to Map</button>
    </form>

  <?php else: ?>
    <!-- Action buttons -->
    <div class="balls-row">
      <span style="font-size:12px;color:var(--muted);">Active: <strong><?php echo htmlspecialchars($myName); ?></strong> (<?php echo $myType; ?>)</span>
      <span class="balls-count">🟡 <?php echo $balls; ?> balls</span>
    </div>

    <button class="btn btn-primary" onclick="document.getElementById('wildMoveOverlay').classList.add('open')">
      ⚔️ Attack
    </button>

    <?php if (count($_SESSION['team'] ?? []) > 1): ?>
    <button class="btn btn-muted" onclick="document.getElementById('wildSwitchOverlay').classList.add('open')" style="margin-top:8px;">
      🔄 Switch Creature
    </button>
    <?php endif; ?>

    <form action="zone2.php" method="POST" style="margin-top:8px;">
      <input type="hidden" name="throw_ball" value="1">
      <button class="btn btn-muted" type="submit">🟡 Throw Ball (<?php echo $balls; ?> left)</button>
    </form>
    <form action="zone2.php" method="POST" style="margin-top:8px;">
      <input type="hidden" name="run_away" value="1">
      <button class="btn btn-muted" type="submit">🏃 Run Away</button>
    </form>

    <!-- Move overlay -->
    <div id="wildMoveOverlay" class="move-overlay">
      <div class="move-panel">
        <h4><?php echo ($typeEmoji[$myType] ?? '⚡'); ?> <?php echo $myType; ?> Moves</h4>
        <?php foreach ($moves as $key => $mv): ?>
        <form action="zone2.php" method="POST">
          <input type="hidden" name="wild_attack" value="1">
          <input type="hidden" name="move" value="<?php echo htmlspecialchars($key); ?>">
          <button type="submit" class="move-btn">
            <span class="move-icon"><?php echo $mv['emoji']; ?></span>
            <span class="move-info">
              <span class="move-name"><?php echo htmlspecialchars($mv['name']); ?></span><br>
              <span class="move-desc"><?php echo htmlspecialchars($mv['desc']); ?></span><br>
              <span class="move-power"><?php echo $mv['power']; ?></span>
            </span>
          </button>
        </form>
        <?php endforeach; ?>
        <button class="cancel-btn" onclick="document.getElementById('wildMoveOverlay').classList.remove('open')">Cancel</button>
      </div>
    </div>

    <!-- Switch overlay -->
    <?php if (count($_SESSION['team'] ?? []) > 1): ?>
    <div id="wildSwitchOverlay" class="switch-overlay">
      <div class="switch-panel">
        <h4>🔄 Switch Creature</h4>
        <div class="switch-warning">⚠️ Enemy gets a free hit when you switch!</div>
        <?php foreach ($_SESSION['team'] as $si => $sm):
          if ($si === 0) continue;
          $fainted = $sm['hp'] <= 0;
          $shp     = max(0, round($sm['hp'] / max(1,$sm['max_hp']) * 100));
          $sc      = $shp > 50 ? '#22c55e' : ($shp > 25 ? '#f59e0b' : '#ef4444');
          $se      = $typeEmoji[$sm['type']] ?? '🐾';
        ?>
          <form action="zone2.php" method="POST">
            <input type="hidden" name="switch_creature" value="1">
            <input type="hidden" name="switch_idx" value="<?php echo $si; ?>">
            <button type="submit" style="width:100%;background:rgba(255,255,255,<?php echo $fainted?'0.02':'0.05';?>);border:1.5px solid rgba(255,255,255,<?php echo $fainted?'0.06':'0.15';?>);border-radius:12px;padding:12px 14px;display:flex;align-items:center;gap:12px;cursor:<?php echo $fainted?'not-allowed':'pointer';?>;opacity:<?php echo $fainted?'0.4':'1';?>;margin-bottom:8px;" <?php if($fainted) echo 'disabled'; ?>>
              <span style="font-size:28px;"><?php echo $se; ?></span>
              <div style="flex:1;text-align:left;">
                <div style="font-family:'Press Start 2P',monospace;font-size:9px;color:var(--text);margin-bottom:4px;"><?php echo htmlspecialchars($sm['name']); ?> <span style="color:var(--muted);">Lv.<?php echo $sm['level']??1;?></span></div>
                <div style="font-size:10px;color:var(--muted);margin-bottom:4px;"><?php echo $sm['type'];?> — <?php echo $sm['hp'];?>/<?php echo $sm['max_hp'];?> HP</div>
                <div style="background:rgba(255,255,255,0.07);border-radius:999px;height:5px;overflow:hidden;"><div style="height:100%;border-radius:999px;width:<?php echo $shp;?>%;background:<?php echo $sc;?>;"></div></div>
              </div>
              <?php echo $fainted ? '<span style="color:var(--red);font-size:12px;font-weight:700;">FAINTED</span>' : '<span style="color:var(--green);font-size:18px;">→</span>'; ?>
            </button>
          </form>
        <?php endforeach; ?>
        <button class="cancel-btn" onclick="document.getElementById('wildSwitchOverlay').classList.remove('open')">Cancel</button>
      </div>
    </div>
    <?php endif; ?>
  <?php endif; ?>

<?php
// ──────────────────────────────────────────────────────────────────────────────
// TRAINER BATTLE
// ──────────────────────────────────────────────────────────────────────────────
elseif ($zone === 'trainer_battle'):
  $trName     = $_SESSION['trainer_name']     ?? 'Trainer Mira';
  $trCreature = $_SESSION['trainer_creature'] ?? 'Frostclaw';
  $trEmoji    = $_SESSION['trainer_emoji']    ?? '❄️';
  $trHp       = $_SESSION['trainer_hp']       ?? 0;
  $trMaxHp    = $_SESSION['trainer_max_hp']   ?? 130;
  $trDefeated = $_SESSION['trainer_defeated'] ?? false;
  $trBattleMsg= $_SESSION['trainer_battle_msg'] ?? '';
  $lvMsg      = $_SESSION['level_up_msg'] ?? '';

  $myHp    = $_SESSION['my_hp']     ?? 0;
  $myMaxHp = $_SESSION['my_max_hp'] ?? 1;
  $myType  = $_SESSION['my_type']   ?? ($_SESSION['team'][0]['type'] ?? 'Normal');
  $myName  = $_SESSION['team'][0]['name'] ?? 'Your Creature';
  $myEmoji = $typeEmoji[$myType] ?? '🐾';
  $myFainted = $myHp <= 0;

  $myHpPct = max(0, round($myHp / max(1,$myMaxHp) * 100));
  $trHpPct = max(0, round($trHp / max(1,$trMaxHp) * 100));
  $myBarColor= $myHpPct > 50 ? '#22c55e' : ($myHpPct > 25 ? '#f59e0b' : '#ef4444');
  $trBarColor= $trHpPct > 50 ? '#22c55e' : ($trHpPct > 25 ? '#f59e0b' : '#ef4444');

  $moves = getMovesForType($myType);
?>
  <h2>⚔️ Trainer Battle!</h2>

  <div class="dialogue-box">
    <strong style="color:var(--accent2);">Trainer Mira:</strong>
    <?php
      if ($trDefeated) echo '"...Well battled. Zone 3 is yours now!"';
      elseif ($myFainted) echo '"Ha! My Frostclaw is unbeatable up here!"';
      else echo '"You dare challenge me on my mountain? I\'ll freeze you solid!"';
    ?>
  </div>

  <div class="battle-msg" style="border-left-color:var(--accent2);">
    <?php echo htmlspecialchars($trBattleMsg); ?>
    <?php if ($lvMsg): ?><br><span style="color:var(--gold);">🎉 <?php echo htmlspecialchars($lvMsg); ?></span><?php endif; ?>
  </div>

  <div class="wild-arena">
    <div class="wild-card mine">
      <span class="wild-sprite"><?php echo $myEmoji; ?></span>
      <h3><?php echo htmlspecialchars($myName); ?></h3>
      <div class="hp-text"><?php echo $myHp; ?> / <?php echo $myMaxHp; ?> HP</div>
      <div class="hp-track"><div class="hp-fill" style="width:<?php echo $myHpPct; ?>%;background:<?php echo $myBarColor; ?>;"></div></div>
    </div>
    <div class="vs-badge">VS</div>
    <div class="wild-card enemy">
      <span class="wild-sprite"><?php echo $trEmoji; ?></span>
      <h3 style="color:var(--accent2);"><?php echo htmlspecialchars($trCreature); ?></h3>
      <div class="hp-text"><?php echo $trHp; ?> / <?php echo $trMaxHp; ?> HP</div>
      <div class="hp-track"><div class="hp-fill" style="width:<?php echo $trHpPct; ?>%;background:<?php echo $trBarColor; ?>;"></div></div>
      <span class="type-badge" style="color:var(--accent2);"><?php echo htmlspecialchars($trName); ?></span>
    </div>
  </div>

  <?php if ($trDefeated): ?>
    <div style="text-align:center;padding:14px 0;">
      <div style="font-size:44px;margin-bottom:8px;">🏆</div>
      <p style="color:var(--gold);font-weight:800;font-size:15px;">You defeated Trainer Mira!</p>
      <p style="font-size:13px;color:var(--muted);margin-top:6px;">Zone 3 unlocked! +5 bonus balls!</p>
    </div>
    <form action="zone2.php" method="POST">
      <input type="hidden" name="advance_zone" value="1">
      <button class="btn btn-success" type="submit">🗺️ Enter Zone 3 →</button>
    </form>

  <?php elseif ($myFainted): ?>
    <div style="text-align:center;padding:10px 0 16px;">
      <div style="font-size:40px;margin-bottom:8px;">💔</div>
      <p style="font-weight:800;font-size:15px;color:var(--red);">Your creature fainted!</p>
    </div>
    <form action="zone2.php" method="POST">
      <input type="hidden" name="run_away" value="1">
      <button class="btn btn-muted" type="submit">← Retreat to Map</button>
    </form>

  <?php else: ?>
    <div class="balls-row">
      <span style="font-size:12px;color:var(--muted);">Active: <strong><?php echo htmlspecialchars($myName); ?></strong></span>
    </div>

    <button class="btn btn-primary" onclick="document.getElementById('trMoveOverlay').classList.add('open')">⚔️ Attack</button>

    <?php if (count($_SESSION['team'] ?? []) > 1): ?>
    <button class="btn btn-muted" onclick="document.getElementById('trSwitchOverlay').classList.add('open')" style="margin-top:8px;">🔄 Switch Creature</button>
    <?php endif; ?>

    <!-- Trainer move overlay -->
    <div id="trMoveOverlay" class="move-overlay">
      <div class="move-panel">
        <h4><?php echo ($typeEmoji[$myType] ?? '⚡'); ?> <?php echo $myType; ?> Moves</h4>
        <?php foreach ($moves as $key => $mv): ?>
        <form action="zone2.php" method="POST">
          <input type="hidden" name="trainer_attack" value="1">
          <input type="hidden" name="move" value="<?php echo htmlspecialchars($key); ?>">
          <button type="submit" class="move-btn">
            <span class="move-icon"><?php echo $mv['emoji']; ?></span>
            <span class="move-info">
              <span class="move-name"><?php echo htmlspecialchars($mv['name']); ?></span><br>
              <span class="move-desc"><?php echo htmlspecialchars($mv['desc']); ?></span><br>
              <span class="move-power"><?php echo $mv['power']; ?></span>
            </span>
          </button>
        </form>
        <?php endforeach; ?>
        <button class="cancel-btn" onclick="document.getElementById('trMoveOverlay').classList.remove('open')">Cancel</button>
      </div>
    </div>

    <!-- Trainer switch overlay -->
    <?php if (count($_SESSION['team'] ?? []) > 1): ?>
    <div id="trSwitchOverlay" class="switch-overlay">
      <div class="switch-panel">
        <h4>🔄 Switch Creature</h4>
        <div class="switch-warning">⚠️ Mira gets a free hit when you switch!</div>
        <?php foreach ($_SESSION['team'] as $si => $sm):
          if ($si === 0) continue;
          $fainted = $sm['hp'] <= 0;
          $shp     = max(0, round($sm['hp'] / max(1,$sm['max_hp']) * 100));
          $sc      = $shp > 50 ? '#22c55e' : ($shp > 25 ? '#f59e0b' : '#ef4444');
          $se      = $typeEmoji[$sm['type']] ?? '🐾';
        ?>
          <form action="zone2.php" method="POST">
            <input type="hidden" name="trainer_switch_creature" value="1">
            <input type="hidden" name="switch_idx" value="<?php echo $si; ?>">
            <button type="submit" style="width:100%;background:rgba(255,255,255,<?php echo $fainted?'0.02':'0.05';?>);border:1.5px solid rgba(255,255,255,<?php echo $fainted?'0.06':'0.15';?>);border-radius:12px;padding:12px 14px;display:flex;align-items:center;gap:12px;cursor:<?php echo $fainted?'not-allowed':'pointer';?>;opacity:<?php echo $fainted?'0.4':'1';?>;margin-bottom:8px;" <?php if($fainted) echo 'disabled'; ?>>
              <span style="font-size:28px;"><?php echo $se; ?></span>
              <div style="flex:1;text-align:left;">
                <div style="font-family:'Press Start 2P',monospace;font-size:9px;margin-bottom:4px;"><?php echo htmlspecialchars($sm['name']); ?> Lv.<?php echo $sm['level']??1;?></div>
                <div style="font-size:10px;color:var(--muted);margin-bottom:4px;"><?php echo $sm['type'];?> — <?php echo $sm['hp'];?>/<?php echo $sm['max_hp'];?> HP</div>
                <div style="background:rgba(255,255,255,0.07);border-radius:999px;height:5px;overflow:hidden;"><div style="height:100%;border-radius:999px;width:<?php echo $shp;?>%;background:<?php echo $sc;?>;"></div></div>
              </div>
              <?php echo $fainted ? '<span style="color:var(--red);font-size:12px;font-weight:700;">FAINTED</span>' : '<span style="color:var(--green);font-size:18px;">→</span>'; ?>
            </button>
          </form>
        <?php endforeach; ?>
        <button class="cancel-btn" onclick="document.getElementById('trSwitchOverlay').classList.remove('open')">Cancel</button>
      </div>
    </div>
    <?php endif; ?>
  <?php endif; ?>

<?php
// ──────────────────────────────────────────────────────────────────────────────
// TEAM SCREEN
// ──────────────────────────────────────────────────────────────────────────────
elseif ($zone === 'team'):
?>
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
    <h2 style="margin:0;">🎒 Your Team</h2>
    <form action="zone2.php" method="POST">
      <input type="hidden" name="close_team" value="1">
      <button class="btn btn-muted" type="submit" style="width:auto;padding:8px 14px;font-size:12px;">← Back</button>
    </form>
  </div>

  <?php foreach ($_SESSION['team'] as $ti => $tm):
    $isActive = ($ti === 0);
    $thpPct   = max(0, round($tm['hp'] / max(1,$tm['max_hp']) * 100));
    $thpCol   = $thpPct > 50 ? '#22c55e' : ($thpPct > 25 ? '#f59e0b' : '#ef4444');
    $txpPct   = isset($tm['xp'],$tm['xp_next']) && $tm['xp_next'] > 0
                ? round($tm['xp'] / $tm['xp_next'] * 100) : 0;
    $te       = $typeEmoji[$tm['type']] ?? '🐾';
    $fainted  = $tm['hp'] <= 0;
  ?>
  <div class="team-card-full" style="<?php echo $isActive ? 'border-color:rgba(147,197,253,0.4);background:rgba(29,111,164,0.08);' : ''; echo $fainted ? 'opacity:0.6;' : ''; ?>">
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:10px;">
      <span style="font-size:32px;"><?php echo $te; ?></span>
      <div style="flex:1;">
        <div style="font-family:'Press Start 2P',monospace;font-size:9px;color:var(--text);">
          <?php echo htmlspecialchars($tm['name']); ?>
          <?php if ($isActive): ?><span style="color:var(--accent2);margin-left:6px;">★ ACTIVE</span><?php endif; ?>
        </div>
        <div style="font-size:11px;color:var(--muted);margin-top:3px;">Lv.<?php echo $tm['level']??1; ?> · <?php echo $tm['type']; ?></div>
      </div>
    </div>
    <!-- HP bar -->
    <div style="font-size:11px;color:var(--muted);margin-bottom:3px;">HP: <?php echo $tm['hp']; ?>/<?php echo $tm['max_hp']; ?><?php echo $fainted ? ' — <span style="color:var(--red);">FAINTED</span>' : ''; ?></div>
    <div style="background:rgba(255,255,255,0.07);border-radius:999px;height:7px;overflow:hidden;margin-bottom:8px;">
      <div style="height:100%;border-radius:999px;width:<?php echo $thpPct; ?>%;background:<?php echo $thpCol; ?>;transition:width .4s;"></div>
    </div>
    <!-- XP bar -->
    <?php if (isset($tm['xp'],$tm['xp_next'])): ?>
    <div style="font-size:10px;color:var(--muted);margin-bottom:3px;">XP: <?php echo $tm['xp']; ?>/<?php echo $tm['xp_next']; ?> to Lv.<?php echo ($tm['level']??1)+1; ?></div>
    <div style="background:rgba(255,255,255,0.07);border-radius:999px;height:5px;overflow:hidden;margin-bottom:8px;">
      <div style="height:100%;border-radius:999px;width:<?php echo $txpPct; ?>%;background:linear-gradient(90deg,#06b6d4,#7c3aed);"></div>
    </div>
    <?php endif; ?>
    <!-- Stats -->
    <div class="stat-grid">
      <div class="stat-box"><span class="stat-val"><?php echo $tm['attack']; ?></span><span class="stat-lbl">⚔️ Atk</span></div>
      <div class="stat-box"><span class="stat-val"><?php echo $tm['defense']; ?></span><span class="stat-lbl">🛡️ Def</span></div>
      <div class="stat-box"><span class="stat-val"><?php echo $tm['speed']; ?></span><span class="stat-lbl">💨 Spd</span></div>
      <div class="stat-box"><span class="stat-val"><?php echo $tm['max_hp']; ?></span><span class="stat-lbl">❤️ MaxHP</span></div>
    </div>
  </div>
  <?php endforeach; ?>

  <?php for ($s = count($_SESSION['team']); $s < 6; $s++): ?>
  <div class="team-card-full" style="opacity:0.25;border-style:dashed;text-align:center;padding:16px;color:var(--muted);font-size:13px;">Empty Slot <?php echo $s+1; ?></div>
  <?php endfor; ?>

<?php endif; ?>

</div><!-- /card -->
</body>
</html>