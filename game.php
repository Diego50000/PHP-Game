<?php
// 1. Start the session
session_start();

// ── HELPER: XP needed to reach next level ──────────────────────────────────
function xpForLevel(int $level): int {
    return $level * $level * 10; // L1→2: 10xp, L2→3: 40xp, L3→4: 90xp …
}

// ── HELPER: Award XP to a single creature slot in the team array ────────────
function awardXP(array &$creature, int $xp): string {
    if (!isset($creature['level']))    $creature['level']    = 1;
    if (!isset($creature['xp']))       $creature['xp']       = 0;
    if (!isset($creature['xp_next']))  $creature['xp_next']  = xpForLevel(1);

    $creature['xp'] += $xp;
    $msg = '';
    while ($creature['xp'] >= $creature['xp_next']) {
        $creature['xp']    -= $creature['xp_next'];
        $creature['level'] += 1;
        // Stat boosts on level-up
        $creature['max_hp']  = round($creature['max_hp']  * 1.08);
        $creature['hp']      = $creature['max_hp'];          // full heal on level-up
        $creature['attack']  = round($creature['attack']  * 1.07);
        $creature['defense'] = round($creature['defense'] * 1.06);
        $creature['speed']   = round($creature['speed']   * 1.05);
        $creature['xp_next'] = xpForLevel($creature['level']);
        $msg .= "{$creature['name']} grew to Lv.{$creature['level']}! ";
    }
    return trim($msg);
}

// ── HELPER: Award XP to the active (index-0) team slot ─────────────────────
function awardXPToActive(int $xp): string {
    if (empty($_SESSION['team'])) return '';
    $msg = awardXP($_SESSION['team'][0], $xp);
    // Also update the shortcut session HP/attack so battle screens stay in sync
    $_SESSION['my_hp']     = $_SESSION['team'][0]['hp'];
    $_SESSION['my_max_hp'] = $_SESSION['team'][0]['max_hp'];
    $_SESSION['my_attack'] = $_SESSION['team'][0]['attack'];
    return $msg;
}

// ── HELPER: Get type-specific moves for a creature type ─────────────────────
function getMovesForType(string $type): array {
    $moves = [
        'Grass' => [
            'leafblade'  => ['name'=>'Leaf Blade',    'emoji'=>'🍃', 'mult'=>1.4, 'desc'=>'Sharp leaves slice the foe.',    'power'=>'★★★☆'],
            'vinewhip'   => ['name'=>'Vine Whip',     'emoji'=>'🌿', 'mult'=>1.0, 'desc'=>'A fast whipping vine attack.',   'power'=>'★★☆☆'],
            'solarbeam'  => ['name'=>'Solar Beam',    'emoji'=>'☀️',  'mult'=>2.0, 'desc'=>'Massive sun-powered blast.',     'power'=>'★★★★'],
            'synthesis'  => ['name'=>'Synthesis',     'emoji'=>'💚', 'mult'=>0.5, 'desc'=>'Heals self, low damage.',        'power'=>'★☆☆☆'],
        ],
        'Water' => [
            'watergun'   => ['name'=>'Water Gun',     'emoji'=>'💦', 'mult'=>1.0, 'desc'=>'A steady stream of water.',      'power'=>'★★☆☆'],
            'hydropump'  => ['name'=>'Hydro Pump',    'emoji'=>'🌊', 'mult'=>2.0, 'desc'=>'Enormous torrent of water.',     'power'=>'★★★★'],
            'aquajet'    => ['name'=>'Aqua Jet',      'emoji'=>'💨', 'mult'=>0.7, 'desc'=>'Strikes first, foe hits less.',  'power'=>'★☆☆☆'],
            'bubblebeam' => ['name'=>'Bubble Beam',   'emoji'=>'🫧', 'mult'=>1.4, 'desc'=>'Bubbles slow the enemy.',        'power'=>'★★★☆'],
        ],
        'Fire' => [
            'ember'      => ['name'=>'Ember',         'emoji'=>'🔥', 'mult'=>1.0, 'desc'=>'A small flame attack.',          'power'=>'★★☆☆'],
            'flamethrower'=> ['name'=>'Flamethrower', 'emoji'=>'🌋', 'mult'=>1.4, 'desc'=>'Intense jet of fire.',           'power'=>'★★★☆'],
            'fireblast'  => ['name'=>'Fire Blast',    'emoji'=>'💥', 'mult'=>2.0, 'desc'=>'A massive explosion of fire.',   'power'=>'★★★★'],
            'quickflame' => ['name'=>'Quick Flame',   'emoji'=>'⚡', 'mult'=>0.7, 'desc'=>'Fast strike, foe hits less.',    'power'=>'★☆☆☆'],
        ],
        'Electric' => [
            'thundershock'=> ['name'=>'Thundershock', 'emoji'=>'⚡', 'mult'=>1.0, 'desc'=>'A jolt of electricity.',         'power'=>'★★☆☆'],
            'thunderbolt' => ['name'=>'Thunderbolt',  'emoji'=>'🌩️', 'mult'=>1.4, 'desc'=>'A powerful electric strike.',   'power'=>'★★★☆'],
            'thunder'     => ['name'=>'Thunder',      'emoji'=>'🌪️', 'mult'=>2.0, 'desc'=>'Massive lightning storm.',      'power'=>'★★★★'],
            'quickcharge' => ['name'=>'Quick Charge', 'emoji'=>'💨', 'mult'=>0.7, 'desc'=>'Fast zap, foe hits less.',       'power'=>'★☆☆☆'],
        ],
        'Bug' => [
            'bugbite'    => ['name'=>'Bug Bite',      'emoji'=>'🐛', 'mult'=>1.0, 'desc'=>'A chomping bite attack.',        'power'=>'★★☆☆'],
            'xscissor'   => ['name'=>'X-Scissor',     'emoji'=>'✂️',  'mult'=>1.4, 'desc'=>'Slashing cross-cut attack.',    'power'=>'★★★☆'],
            'bugbuzz'    => ['name'=>'Bug Buzz',      'emoji'=>'📳', 'mult'=>2.0, 'desc'=>'Sonic vibration wave.',          'power'=>'★★★★'],
            'stringshot' => ['name'=>'String Shot',   'emoji'=>'🕸️', 'mult'=>0.7, 'desc'=>'Slows enemy, low damage.',      'power'=>'★☆☆☆'],
        ],
        'Flying' => [
            'gust'       => ['name'=>'Gust',          'emoji'=>'🌬️', 'mult'=>1.0, 'desc'=>'A sharp gust of wind.',         'power'=>'★★☆☆'],
            'airslash'   => ['name'=>'Air Slash',     'emoji'=>'🦅', 'mult'=>1.4, 'desc'=>'Razor-sharp air blade.',         'power'=>'★★★☆'],
            'hurricane'  => ['name'=>'Hurricane',     'emoji'=>'🌀', 'mult'=>2.0, 'desc'=>'A raging wind storm.',           'power'=>'★★★★'],
            'quickwind'  => ['name'=>'Quick Wind',    'emoji'=>'💨', 'mult'=>0.7, 'desc'=>'Swift strike, foe hits less.',   'power'=>'★☆☆☆'],
        ],
        'Rock' => [
            'rockthrow'  => ['name'=>'Rock Throw',   'emoji'=>'🪨', 'mult'=>1.0, 'desc'=>'Hurls a sharp boulder.',          'power'=>'★★☆☆'],
            'rockslide'  => ['name'=>'Rock Slide',   'emoji'=>'🏔️', 'mult'=>1.4, 'desc'=>'Raining rocks hit hard.',         'power'=>'★★★☆'],
            'stoneedge'  => ['name'=>'Stone Edge',   'emoji'=>'💎', 'mult'=>2.0, 'desc'=>'Jagged stone critical strike.',    'power'=>'★★★★'],
            'rollout'    => ['name'=>'Rollout',       'emoji'=>'⚪', 'mult'=>0.7, 'desc'=>'Defensive roll, foe hits less.',  'power'=>'★☆☆☆'],
        ],
        'Ice' => [
            'iceshard'   => ['name'=>'Ice Shard',    'emoji'=>'🧊', 'mult'=>1.0, 'desc'=>'Fast frozen fragment.',           'power'=>'★★☆☆'],
            'icebeam'    => ['name'=>'Ice Beam',     'emoji'=>'❄️',  'mult'=>1.4, 'desc'=>'Freezing concentrated beam.',    'power'=>'★★★☆'],
            'blizzard'   => ['name'=>'Blizzard',     'emoji'=>'🌨️', 'mult'=>2.0, 'desc'=>'Devastating snowstorm.',         'power'=>'★★★★'],
            'frostbreath'=> ['name'=>'Frost Breath', 'emoji'=>'💨', 'mult'=>0.7, 'desc'=>'Chilling gust, foe hits less.',   'power'=>'★☆☆☆'],
        ],
    ];
        'tackle'  => ['name'=>'Tackle',        'emoji'=>'⚡', 'mult'=>1.0, 'desc'=>'A basic body slam.',        'power'=>'★★☆☆'],
        'slam'    => ['name'=>'Slam',           'emoji'=>'💥', 'mult'=>1.5, 'desc'=>'Heavy blow.',               'power'=>'★★★☆'],
        'quick'   => ['name'=>'Quick Hit',      'emoji'=>'💨', 'mult'=>0.7, 'desc'=>'Fast, foe hits less.',      'power'=>'★☆☆☆'],
        'special' => ['name'=>'Special Blast',  'emoji'=>'✨', 'mult'=>2.0, 'desc'=>'Massive power — risky!',    'power'=>'★★★★'],
    ];
}

// ── HELPER: Resolve a move key against a type's moveset ─────────────────────
function resolveMove(string $moveKey, string $type): array {
    $moves = getMovesForType($type);
    return $moves[$moveKey] ?? array_values($moves)[0];
}

// ── HELPER: Sync session my_hp/my_attack from team slot 0 ───────────────────
function syncActiveFromTeam(): void {
    if (!empty($_SESSION['team'])) {
        $_SESSION['my_hp']     = $_SESSION['team'][0]['hp'];
        $_SESSION['my_max_hp'] = $_SESSION['team'][0]['max_hp'];
        $_SESSION['my_attack'] = $_SESSION['team'][0]['attack'];
        $_SESSION['my_type']   = $_SESSION['team'][0]['type'];
    }
}

// ── HELPER: Build a fresh team-member array from a creature data row ────────
function makeTeamMember(array $c): array {
    return [
        'name'    => $c['name'],
        'type'    => $c['type'],
        'hp'      => $c['hp'],
        'max_hp'  => $c['hp'],
        'attack'  => $c['attack'],
        'defense' => $c['defense'],
        'speed'   => $c['speed'],
        'level'   => 1,
        'xp'      => 0,
        'xp_next' => xpForLevel(1),
    ];
}

// 1. Restart
if (isset($_POST['restart'])) {
    session_unset();
    $_SESSION['zone'] = 0;
}

// 2. Login
if (isset($_POST['playerName'])) {
    $_SESSION['playerName'] = $_POST['playerName'];
    $_SESSION['difficulty'] = $_POST['difficulty'];
    $_SESSION['zone'] = 0;
    if (!isset($_SESSION['team']))  $_SESSION['team']  = [];
    if (!isset($_SESSION['balls'])) $_SESSION['balls'] = 5;
}

// 3. Load JSON
$jsonData = file_get_contents('data/creatures.json');
$creaturesData = json_decode($jsonData, true);

// 4. Starter chosen → add to team as level-1 member
if (isset($_POST['starter'])) {
    $_SESSION['starter'] = $_POST['starter'];
    $_SESSION['zone'] = 'rival_dialogue';

    // Build starter team entry
    foreach ($creaturesData['creatures'] as $c) {
        if ($c['category'] === 'starter' && strtolower($c['type']) === $_POST['starter']) {
            if (empty($_SESSION['team'])) {
                $_SESSION['team'][] = makeTeamMember($c);
            }
            break;
        }
    }
}

// 5. Start rival battle
if (isset($_POST['start_rival_battle'])) {
    $_SESSION['zone'] = 'rival_battle';
    foreach ($creaturesData['creatures'] as $creature) {
        if ($creature['category'] == 'starter' && strtolower($creature['type']) == $_SESSION['starter']) {
            $_SESSION['my_max_hp'] = !empty($_SESSION['team']) ? $_SESSION['team'][0]['max_hp'] : $creature['hp'];
            $_SESSION['my_hp']     = $_SESSION['my_max_hp'];
            $_SESSION['my_attack'] = !empty($_SESSION['team']) ? $_SESSION['team'][0]['attack'] : $creature['attack'];
        }
        if ($creature['category'] == 'rival') {
            $_SESSION['rival_max_hp'] = $creature['hp'];
            $_SESSION['rival_hp']     = $creature['hp'];
            $_SESSION['rival_attack'] = $creature['attack'];
        }
    }
    $_SESSION['battle_message'] = "The battle has begun! Choose your move!";
    $_SESSION['level_up_msg'] = '';
    syncActiveFromTeam();
}

// 6. Rival battle attack
if (isset($_POST['attack_rival'])) {
    $move = $_POST['move'] ?? '';
    $activeType = $_SESSION['my_type'] ?? ($_SESSION['team'][0]['type'] ?? 'Normal');
    $chosen = resolveMove($move, $activeType);
    $damageToRival = max(1, round(($_SESSION['my_attack'] / 3) * $chosen['mult']));
    $isQuick = ($chosen['mult'] <= 0.75);
    $counterMult = $isQuick ? 0.5 : 1.0;
    $damageToMe   = max(1, round(($_SESSION['rival_attack'] / 3) * $counterMult));
    $_SESSION['last_move'] = $chosen['name'];
    $_SESSION['rival_hp'] -= $damageToRival;
    $_SESSION['level_up_msg'] = '';

    if ($_SESSION['rival_hp'] <= 0) {
        $_SESSION['rival_hp'] = 0;
        $lvMsg = awardXPToActive(30);
        $_SESSION['level_up_msg'] = $lvMsg;
        $_SESSION['battle_message'] = "You used {$chosen['emoji']} {$chosen['name']} for $damageToRival damage! You defeated Sparkpup! (+30 XP)" . ($lvMsg ? " 🎉 $lvMsg" : '');
    } else {
        $_SESSION['my_hp'] -= $damageToMe;
        $_SESSION['battle_message'] = "You used {$chosen['emoji']} {$chosen['name']} for $damageToRival damage! Sparkpup fought back for $damageToMe damage!";
        if ($_SESSION['my_hp'] <= 0) {
            $_SESSION['my_hp'] = 0;
            $_SESSION['battle_message'] = "Oh no! Your creature fainted. Game Over!";
        }
    }
    if (!empty($_SESSION['team'])) $_SESSION['team'][0]['hp'] = $_SESSION['my_hp'];
}

// 7. Finish rival battle
if (isset($_POST['finish_rival_battle'])) {
    $_SESSION['zone'] = 1;
    $_SESSION['last_map_zone'] = 1;
}

// 8. Wild encounter starts
if (isset($_POST['wild_encounter'])) {
    $_SESSION['zone'] = 'battle';
    $currentZone = $_SESSION['prev_battle_zone'] ?? ($_SESSION['zone'] == 'battle' ? ($_SESSION['last_map_zone'] ?? 1) : 1);
    // Use last_map_zone to know which zone the encounter came from
    $zoneCategory = 'wild_zone' . ($_SESSION['last_map_zone'] ?? 1);
    $wildPool = array_values(array_filter($creaturesData['creatures'], function($c) use ($zoneCategory) { return $c['category'] === $zoneCategory; }));
    if (empty($wildPool)) {
        $wildPool = array_values(array_filter($creaturesData['creatures'], function($c) { return $c['category'] === 'wild_zone1'; }));
    }
    $picked = $wildPool[array_rand($wildPool)];
    $_SESSION['wild_name']       = $picked['name'];
    $_SESSION['wild_type']       = $picked['type'];
    $_SESSION['wild_max_hp']     = $picked['hp'];
    $_SESSION['wild_hp']         = $picked['hp'];
    $_SESSION['wild_attack']     = $picked['attack'];
    $_SESSION['wild_defense']    = $picked['defense'];
    $_SESSION['wild_speed']      = $picked['speed'];
    if (!isset($_SESSION['balls'])) $_SESSION['balls'] = 5;
    if (!isset($_SESSION['team']))  $_SESSION['team']  = [];
    $_SESSION['wild_battle_msg'] = "A wild {$picked['name']} appeared! What will you do?";
    $_SESSION['wild_caught']     = false;
    $_SESSION['wild_fled']       = false;
    $_SESSION['level_up_msg']    = '';
    syncActiveFromTeam();
}

// 9. Wild attack
if (isset($_POST['wild_attack'])) {
    $move = $_POST['move'] ?? '';
    $activeType = $_SESSION['my_type'] ?? ($_SESSION['team'][0]['type'] ?? 'Normal');
    $chosen = resolveMove($move, $activeType);
    $dmgToWild = max(1, round(($_SESSION['my_attack'] / 3) * $chosen['mult']));
    $isQuick = ($chosen['mult'] <= 0.75);
    $counterMult = $isQuick ? 0.5 : 1.0;
    $dmgToMe = max(1, round(($_SESSION['wild_attack'] / 3) * $counterMult));
    $_SESSION['level_up_msg'] = '';
    $_SESSION['wild_hp'] -= $dmgToWild;

    if ($_SESSION['wild_hp'] <= 0) {
        $_SESSION['wild_hp'] = 0;
        if (!isset($_SESSION['kills'])) $_SESSION['kills'] = 0;
        $_SESSION['kills']++;
        $kills = $_SESSION['kills'];
        $lvMsg = awardXPToActive(15);
        $_SESSION['level_up_msg'] = $lvMsg;
        $killNote = " ($kills/5 defeated)";
        if ($kills >= 5 && !($_SESSION['trainer_unlocked'] ?? false)) {
            $_SESSION['trainer_unlocked'] = true;
            $killNote = " 🏆 5 defeated! Find the trainer on the map!";
        }
        $_SESSION['wild_battle_msg'] = "You used {$chosen['emoji']} {$chosen['name']} for $dmgToWild damage! Wild {$_SESSION['wild_name']} fainted! (+15 XP){$killNote}" . ($lvMsg ? " 🎉 $lvMsg" : '');
    } else {
        $_SESSION['my_hp'] -= $dmgToMe;
        if (!empty($_SESSION['team'])) $_SESSION['team'][0]['hp'] = $_SESSION['my_hp'];
        if ($_SESSION['my_hp'] <= 0) {
            $_SESSION['my_hp'] = 0;
            $_SESSION['wild_battle_msg'] = "You used {$chosen['emoji']} {$chosen['name']}! But {$_SESSION['wild_name']} hit back for $dmgToMe and your creature fainted!";
        } else {
            $_SESSION['wild_battle_msg'] = "You used {$chosen['emoji']} {$chosen['name']} for $dmgToWild damage! {$_SESSION['wild_name']} hit back for $dmgToMe!";
        }
    }
}

// 10. Throw ball
if (isset($_POST['throw_ball'])) {
    if (!isset($_SESSION['balls'])) $_SESSION['balls'] = 0;
    $_SESSION['level_up_msg'] = '';
    if ($_SESSION['balls'] > 0) {
        $_SESSION['balls']--;
        $hpRatio  = $_SESSION['wild_hp'] / $_SESSION['wild_max_hp'];
        $catchRate = 0.25 + (1 - $hpRatio) * 0.55;
        if (rand(1,100) <= ($catchRate * 100)) {
            if (!isset($_SESSION['team'])) $_SESSION['team'] = [];
            $newMember = makeTeamMember([
                'name'    => $_SESSION['wild_name'],
                'type'    => $_SESSION['wild_type'],
                'hp'      => $_SESSION['wild_max_hp'],
                'attack'  => $_SESSION['wild_attack'],
                'defense' => $_SESSION['wild_defense'],
                'speed'   => $_SESSION['wild_speed'],
            ]);
            $lvMsg = awardXP($newMember, 5); // tiny XP for catching
            $_SESSION['team'][] = $newMember;
            $_SESSION['wild_caught'] = true;
            $_SESSION['wild_battle_msg'] = "Gotcha! {$_SESSION['wild_name']} was caught and added to your team! 🎉";
        } else {
            $dmgToMe = max(1, round($_SESSION['wild_attack'] / 4));
            $_SESSION['my_hp'] = max(0, $_SESSION['my_hp'] - $dmgToMe);
            if (!empty($_SESSION['team'])) $_SESSION['team'][0]['hp'] = $_SESSION['my_hp'];
            $ballsLeft = $_SESSION['balls'];
            $_SESSION['wild_battle_msg'] = "Oh no! {$_SESSION['wild_name']} broke free and hit you for $dmgToMe! ($ballsLeft balls left)";
        }
    } else {
        $_SESSION['wild_battle_msg'] = "You're out of balls! Attack or run away!";
    }
}

// 11. Run away
if (isset($_POST['run_away'])) {
    $_SESSION['zone'] = $_SESSION['last_map_zone'] ?? 1;
    unset($_SESSION['wild_name'], $_SESSION['wild_type'], $_SESSION['wild_hp'],
          $_SESSION['wild_max_hp'], $_SESSION['wild_attack'], $_SESSION['wild_defense'],
          $_SESSION['wild_speed'], $_SESSION['wild_battle_msg'], $_SESSION['wild_caught'], $_SESSION['wild_fled']);
}

// 12. Leave battle
if (isset($_POST['leave_battle'])) {
    $_SESSION['zone'] = $_SESSION['last_map_zone'] ?? 1;
    unset($_SESSION['wild_name'], $_SESSION['wild_type'], $_SESSION['wild_hp'],
          $_SESSION['wild_max_hp'], $_SESSION['wild_attack'], $_SESSION['wild_defense'],
          $_SESSION['wild_speed'], $_SESSION['wild_battle_msg'], $_SESSION['wild_caught'], $_SESSION['wild_fled']);
}

// 13. Trainer encounter — triggered from the map
if (isset($_POST['trainer_encounter'])) {
    if (!($_SESSION['trainer_unlocked'] ?? false)) {
        $_SESSION['zone'] = $_SESSION['last_map_zone'] ?? 1;
    } else {
        $_SESSION['zone'] = 'trainer_battle';
        $mapZone = $_SESSION['last_map_zone'] ?? 1;
        if ($mapZone == 2) {
            // Trainer Mira — tougher, Ice/Rock type
            $_SESSION['trainer_name']       = 'Trainer Mira';
            $_SESSION['trainer_creature']   = 'Frostclaw';
            $_SESSION['trainer_emoji']      = '❄️';
            $_SESSION['trainer_hp']         = 130;
            $_SESSION['trainer_max_hp']     = 130;
            $_SESSION['trainer_attack']     = 95;
            $_SESSION['trainer_battle_msg'] = "Trainer Mira sends out Frostclaw! What will you do?";
        } else {
            // Trainer Rex — Zone 1
            $_SESSION['trainer_name']       = 'Trainer Rex';
            $_SESSION['trainer_creature']   = 'Emberfox';
            $_SESSION['trainer_emoji']      = '🔥';
            $_SESSION['trainer_hp']         = 90;
            $_SESSION['trainer_max_hp']     = 90;
            $_SESSION['trainer_attack']     = 75;
            $_SESSION['trainer_battle_msg'] = "Trainer Rex sends out Emberfox! What will you do?";
        }
        $_SESSION['trainer_defeated'] = false;
        $_SESSION['level_up_msg']     = '';
        syncActiveFromTeam();
    }
}

// 14. Trainer battle — attack
if (isset($_POST['attack_trainer'])) {
    $move = $_POST['move'] ?? '';
    $activeType = $_SESSION['my_type'] ?? ($_SESSION['team'][0]['type'] ?? 'Normal');
    $chosen = resolveMove($move, $activeType);
    $dmgToTrainer = max(1, round(($_SESSION['my_attack'] / 3) * $chosen['mult']));
    $isQuick = ($chosen['mult'] <= 0.75);
    $counterMult = $isQuick ? 0.5 : 1.0;
    $dmgToMe     = max(1, round(($_SESSION['trainer_attack'] / 3) * $counterMult));
    $_SESSION['level_up_msg'] = '';
    $_SESSION['trainer_hp'] -= $dmgToTrainer;

    if ($_SESSION['trainer_hp'] <= 0) {
        $_SESSION['trainer_hp'] = 0;
        $_SESSION['trainer_defeated'] = true;
        $lvMsg = awardXPToActive(50);
        $_SESSION['level_up_msg'] = $lvMsg;
        $tName = $_SESSION['trainer_creature'] ?? 'Emberfox';
        $tTrainer = $_SESSION['trainer_name'] ?? 'the trainer';
        $_SESSION['trainer_battle_msg'] = "You dealt $dmgToTrainer damage! $tName fainted! You beat $tTrainer! (+50 XP)" . ($lvMsg ? " 🎉 $lvMsg" : '');
    } else {
        $_SESSION['my_hp'] -= $dmgToMe;
        if (!empty($_SESSION['team'])) $_SESSION['team'][0]['hp'] = $_SESSION['my_hp'];
        $tName = $_SESSION['trainer_creature'] ?? 'Emberfox';
        if ($_SESSION['my_hp'] <= 0) {
            $_SESSION['my_hp'] = 0;
            $_SESSION['trainer_battle_msg'] = "You dealt $dmgToTrainer damage but $tName hit back for $dmgToMe! Your creature fainted!";
        } else {
            $_SESSION['trainer_battle_msg'] = "You used {$chosen['emoji']} {$chosen['name']} for $dmgToTrainer damage! $tName hit back for $dmgToMe!";
        }
    }
}

// 15. Switch active creature during battle
if (isset($_POST['switch_creature'])) {
    $idx = (int)$_POST['switch_idx'];
    if (isset($_SESSION['team'][$idx]) && $idx !== 0 && $_SESSION['team'][$idx]['hp'] > 0) {
        // Swap chosen creature to slot 0
        $tmp = $_SESSION['team'][0];
        $_SESSION['team'][0] = $_SESSION['team'][$idx];
        $_SESSION['team'][$idx] = $tmp;
        syncActiveFromTeam();
        $switched = $_SESSION['team'][0]['name'];
        // Update the right battle message
        $switchMsg = "You switched to {$switched}!";
        if ($_SESSION['zone'] === 'battle') {
            // Enemy gets a free hit when you switch
            $freeDmg = max(1, round($_SESSION['wild_attack'] / 3));
            $_SESSION['my_hp'] = max(0, $_SESSION['my_hp'] - $freeDmg);
            $_SESSION['team'][0]['hp'] = $_SESSION['my_hp'];
            $_SESSION['wild_battle_msg'] = "$switchMsg {$_SESSION['wild_name']} attacked for $freeDmg while you were switching!";
        } elseif ($_SESSION['zone'] === 'trainer_battle') {
            $freeDmg = max(1, round($_SESSION['trainer_attack'] / 3));
            $_SESSION['my_hp'] = max(0, $_SESSION['my_hp'] - $freeDmg);
            $_SESSION['team'][0]['hp'] = $_SESSION['my_hp'];
            $_SESSION['trainer_battle_msg'] = "$switchMsg Emberfox attacked for $freeDmg while you were switching!";
        } elseif ($_SESSION['zone'] === 'rival_battle') {
            $freeDmg = max(1, round($_SESSION['rival_attack'] / 3));
            $_SESSION['my_hp'] = max(0, $_SESSION['my_hp'] - $freeDmg);
            $_SESSION['team'][0]['hp'] = $_SESSION['my_hp'];
            $_SESSION['battle_message'] = "$switchMsg Sparkpup attacked for $freeDmg while you were switching!";
        }
    }
}


if (isset($_POST['advance_zone'])) {
    $from = $_SESSION['last_map_zone'] ?? 1;
    $next = $from + 1;
    $_SESSION['zone'] = $next;
    $_SESSION['last_map_zone'] = $next;
    $_SESSION['kills'] = 0;
    $_SESSION['trainer_unlocked'] = false;
    $_SESSION['balls'] = ($_SESSION['balls'] ?? 0) + 5;
    unset($_SESSION['trainer_hp'], $_SESSION['trainer_max_hp'], $_SESSION['trainer_attack'],
          $_SESSION['trainer_battle_msg'], $_SESSION['trainer_defeated'], $_SESSION['trainer_name'],
          $_SESSION['trainer_creature'], $_SESSION['trainer_emoji']);
}

// 16. Open team screen
if (isset($_POST['open_team'])) {
    $_SESSION['prev_zone'] = $_SESSION['zone'];
    $_SESSION['zone'] = 'team';
}

// 17. Close team screen
if (isset($_POST['close_team'])) {
    $_SESSION['zone'] = $_SESSION['prev_zone'] ?? 1;
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Pokequest</title>
  <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --bg: #1a1a2e;
      --surface: rgba(255,255,255,0.04);
      --border: rgba(255,255,255,0.08);
      --text: #e0e0f0;
      --muted: #6b7db3;
      --accent: #7c3aed;
      --accent2: #06b6d4;
      --gold: #fbbf24;
      --red: #ef4444;
      --green: #22c55e;
      --player-color: #06b6d4;
      --rival-color: #ef4444;
    }

    body {
      background-color: var(--bg);
      background-image:
        radial-gradient(ellipse at 20% 50%, rgba(99,0,210,0.15) 0%, transparent 60%),
        radial-gradient(ellipse at 80% 20%, rgba(0,180,216,0.12) 0%, transparent 50%);
      min-height: 100vh;
      font-family: 'Nunito', sans-serif;
      color: var(--text);
      display: flex;
      flex-direction: column;
      align-items: center;
      padding: 20px;
    }

    /* --- HEADER BAR --- */
    .hud {
      width: 100%;
      max-width: 640px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 28px;
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: 14px;
      padding: 10px 20px;
      font-size: 13px;
    }

    .hud-logo {
      font-family: 'Press Start 2P', monospace;
      font-size: 11px;
      color: var(--gold);
      text-shadow: 0 0 10px rgba(251,191,36,0.4);
    }

    .hud-info { color: var(--muted); }
    .hud-info strong { color: var(--text); }

    /* --- CARD --- */
    .card {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: 20px;
      padding: 32px;
      width: 100%;
      max-width: 560px;
      backdrop-filter: blur(10px);
      box-shadow: 0 20px 60px rgba(0,0,0,0.4);
    }

    h2 {
      font-family: 'Press Start 2P', monospace;
      font-size: 14px;
      color: var(--gold);
      text-shadow: 0 0 20px rgba(251,191,36,0.3);
      margin-bottom: 20px;
      line-height: 1.6;
    }

    p { line-height: 1.6; color: var(--muted); }

    /* --- BUTTONS --- */
    .btn {
      display: inline-block;
      padding: 12px 24px;
      border: none;
      border-radius: 10px;
      font-family: 'Press Start 2P', monospace;
      font-size: 10px;
      cursor: pointer;
      transition: transform 0.15s, box-shadow 0.15s;
      letter-spacing: 0.5px;
    }

    .btn:hover { transform: translateY(-2px); }
    .btn:active { transform: translateY(0); }

    .btn-primary {
      background: linear-gradient(135deg, var(--accent), #4f46e5);
      color: white;
      box-shadow: 0 4px 20px rgba(124,58,237,0.4);
    }
    .btn-primary:hover { box-shadow: 0 8px 28px rgba(124,58,237,0.5); }

    .btn-danger {
      background: linear-gradient(135deg, var(--red), #b91c1c);
      color: white;
      box-shadow: 0 4px 20px rgba(239,68,68,0.35);
    }

    .btn-success {
      background: linear-gradient(135deg, var(--green), #16a34a);
      color: white;
      box-shadow: 0 4px 20px rgba(34,197,94,0.35);
    }

    .btn-ghost {
      background: var(--surface);
      border: 1px solid var(--border);
      color: var(--text);
    }
    .btn-ghost:hover { border-color: var(--accent2); }

    /* --- STARTER SELECTION --- */
    .starter-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 14px;
      margin: 20px 0;
    }

    .starter-option { position: relative; }
    .starter-option input[type="radio"] { position: absolute; opacity: 0; width: 0; height: 0; }

    .starter-card {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 10px;
      padding: 20px 10px;
      border: 2px solid var(--border);
      border-radius: 16px;
      cursor: pointer;
      transition: all 0.2s;
      background: rgba(255,255,255,0.02);
    }

    .starter-card:hover { border-color: rgba(255,255,255,0.2); background: rgba(255,255,255,0.05); }

    .starter-option input:checked + .starter-card {
      border-color: var(--accent);
      background: rgba(124,58,237,0.15);
      box-shadow: 0 0 20px rgba(124,58,237,0.3);
    }

    .starter-emoji { font-size: 36px; }
    .starter-name { font-family: 'Press Start 2P', monospace; font-size: 8px; color: var(--text); }

    /* --- BATTLE LAYOUT --- */
    .battle-arena {
      display: grid;
      grid-template-columns: 1fr 60px 1fr;
      align-items: center;
      gap: 10px;
      margin: 20px 0;
    }

    .vs-badge {
      font-family: 'Press Start 2P', monospace;
      font-size: 10px;
      color: var(--gold);
      text-align: center;
    }

    .creature-card {
      border-radius: 16px;
      padding: 16px;
      border: 2px solid;
    }

    .creature-card.mine { border-color: rgba(6,182,212,0.4); background: rgba(6,182,212,0.05); }
    .creature-card.rival { border-color: rgba(239,68,68,0.4); background: rgba(239,68,68,0.05); }

    .creature-card h3 {
      font-family: 'Press Start 2P', monospace;
      font-size: 9px;
      margin-bottom: 12px;
      line-height: 1.5;
    }

    .creature-card.mine h3 { color: var(--player-color); }
    .creature-card.rival h3 { color: var(--rival-color); }

    .hp-text {
      font-size: 12px;
      font-weight: 700;
      color: var(--muted);
      margin-bottom: 6px;
    }

    .hp-bar-wrap {
      background: rgba(255,255,255,0.08);
      border-radius: 999px;
      height: 10px;
      overflow: hidden;
    }

    .hp-bar {
      height: 100%;
      border-radius: 999px;
      transition: width 0.5s ease;
    }

    .hp-bar.mine { background: linear-gradient(90deg, var(--player-color), #0284c7); }
    .hp-bar.rival { background: linear-gradient(90deg, var(--rival-color), #dc2626); }

    /* --- BATTLE MESSAGE BOX --- */
    .battle-msg {
      background: rgba(255,255,255,0.04);
      border: 1px solid var(--border);
      border-left: 3px solid var(--gold);
      border-radius: 12px;
      padding: 14px 18px;
      font-size: 14px;
      font-weight: 700;
      color: var(--text);
      margin-bottom: 20px;
    }

    /* --- ATTACK MENU --- */
    .attack-trigger-btn {
      width: 100%;
      padding: 14px;
      background: linear-gradient(135deg, rgba(124,58,237,0.2), rgba(79,70,229,0.2));
      border: 2px solid var(--accent);
      border-radius: 12px;
      color: white;
      font-family: 'Press Start 2P', monospace;
      font-size: 10px;
      cursor: pointer;
      letter-spacing: 1px;
      transition: all 0.2s;
    }

    .attack-trigger-btn:hover {
      background: linear-gradient(135deg, rgba(124,58,237,0.4), rgba(79,70,229,0.4));
      box-shadow: 0 0 20px rgba(124,58,237,0.3);
    }

    /* Attack menu overlay */
    .attack-menu-overlay {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(0,0,0,0.7);
      backdrop-filter: blur(4px);
      z-index: 100;
      align-items: flex-end;
      justify-content: center;
      padding: 20px;
    }

    .attack-menu-overlay.open { display: flex; }

    .attack-menu {
      background: #16162a;
      border: 1px solid rgba(255,255,255,0.12);
      border-radius: 24px 24px 20px 20px;
      padding: 28px;
      width: 100%;
      max-width: 500px;
      animation: slideUp 0.25s ease;
    }

    @keyframes slideUp {
      from { transform: translateY(40px); opacity: 0; }
      to   { transform: translateY(0);    opacity: 1; }
    }

    .attack-menu h3 {
      font-family: 'Press Start 2P', monospace;
      font-size: 10px;
      color: var(--gold);
      margin-bottom: 18px;
      text-align: center;
    }

    .moves-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 12px;
      margin-bottom: 16px;
    }

    .move-btn {
      background: rgba(255,255,255,0.04);
      border: 2px solid rgba(255,255,255,0.1);
      border-radius: 14px;
      padding: 14px 12px;
      cursor: pointer;
      transition: all 0.2s;
      text-align: left;
      width: 100%;
    }

    .move-btn:hover {
      border-color: var(--accent);
      background: rgba(124,58,237,0.15);
      transform: translateY(-2px);
      box-shadow: 0 4px 16px rgba(124,58,237,0.25);
    }

    .move-emoji { font-size: 24px; display: block; margin-bottom: 6px; }

    .move-name {
      font-family: 'Press Start 2P', monospace;
      font-size: 8px;
      color: var(--text);
      display: block;
      margin-bottom: 5px;
    }

    .move-desc {
      font-size: 11px;
      color: var(--muted);
      line-height: 1.4;
    }

    .move-power {
      font-size: 10px;
      font-weight: 800;
      color: var(--gold);
      margin-top: 4px;
    }

    .cancel-btn {
      width: 100%;
      padding: 12px;
      background: transparent;
      border: 1px solid var(--border);
      border-radius: 10px;
      color: var(--muted);
      font-family: 'Nunito', sans-serif;
      font-size: 14px;
      cursor: pointer;
      transition: all 0.2s;
    }

    .cancel-btn:hover { border-color: rgba(255,255,255,0.2); color: var(--text); }

    /* --- ZONE 1 GRID --- */
    #game-board {
      display: grid;
      grid-template-columns: repeat(5, 58px);
      grid-template-rows: repeat(5, 58px);
      gap: 3px;
      background: #0f172a;
      border: 2px solid var(--border);
      border-radius: 12px;
      padding: 6px;
      margin: 20px 0;
    }

    .tile {
      width: 58px;
      height: 58px;
      background-color: #1a4731;
      border-radius: 6px;
      transition: background-color 0.1s;
    }

    .player {
      background: radial-gradient(circle, #06b6d4, #0284c7) !important;
      border-radius: 50%;
      box-shadow: 0 0 12px rgba(6,182,212,0.6);
    }

    /* --- DIALOGUE --- */
    .dialogue-box {
      background: rgba(255,255,255,0.04);
      border: 1px solid var(--border);
      border-radius: 16px;
      padding: 20px 24px;
      margin: 20px 0;
      font-size: 15px;
      font-weight: 600;
      color: var(--text);
      line-height: 1.7;
    }

    .dialogue-box strong { color: var(--player-color); }

    /* --- FORMS --- */
    form { margin-top: 0; }
  </style>
</head>
<body>

  <div class="hud">
    <span class="hud-logo">⚡ PQ</span>
    <span class="hud-info">
      <strong><?php echo isset($_SESSION['playerName']) ? htmlspecialchars($_SESSION['playerName']) : 'Guest'; ?></strong>
      &nbsp;·&nbsp; Zone: <strong><?php echo isset($_SESSION['zone']) ? $_SESSION['zone'] : '0'; ?></strong>
      &nbsp;·&nbsp; 🟡 <strong><?php echo $_SESSION['balls'] ?? 0; ?></strong>
    </span>
    <?php if (!empty($_SESSION['team']) && !in_array($_SESSION['zone'] ?? '', ['team', 0, 'rival_dialogue'])): ?>
    <form action="game.php" method="POST" style="margin:0;">
      <input type="hidden" name="open_team" value="true">
      <button type="submit" style="background:rgba(251,191,36,0.15);border:1px solid rgba(251,191,36,0.4);color:#fbbf24;font-family:'Nunito',sans-serif;font-size:12px;font-weight:800;padding:5px 14px;border-radius:20px;cursor:pointer;transition:all 0.2s;">
        🎒 Team (<?php echo count($_SESSION['team']); ?>)
      </button>
    </form>
    <?php endif; ?>
  </div>

  <?php
  // Objective progress bar — show during zone 1 and battle screens
  $showObjective = in_array($_SESSION['zone'] ?? '', [1, 'battle', 'trainer_battle', 2]);
  if ($showObjective):
    $kills = $_SESSION['kills'] ?? 0;
    $trainerUnlocked = $_SESSION['trainer_unlocked'] ?? false;
    $killsPct = min(100, round($kills / 5 * 100));
  ?>
  <div style="width:100%;max-width:560px;margin-bottom:14px;">
    <div style="background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.08);border-radius:12px;padding:10px 16px;">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
        <span style="font-family:'Press Start 2P',monospace;font-size:8px;color:var(--gold);">
          <?php if ($trainerUnlocked): ?>⚠️ Find Trainer Rex!<?php else: ?>🎯 Defeat <?php echo 5 - $kills; ?> more wild creatures<?php endif; ?>
        </span>
        <span style="font-size:11px;font-weight:800;color:var(--muted);"><?php echo $kills; ?>/5</span>
      </div>
      <div style="background:rgba(255,255,255,0.07);border-radius:999px;height:7px;overflow:hidden;">
        <div style="height:100%;border-radius:999px;width:<?php echo $killsPct; ?>%;background:<?php echo $trainerUnlocked ? 'linear-gradient(90deg,#f59e0b,#ef4444)' : 'linear-gradient(90deg,#22c55e,#06b6d4)'; ?>;transition:width 0.5s;"></div>
      </div>
      <?php if ($trainerUnlocked): ?>
        <div style="font-size:11px;color:#f59e0b;margin-top:5px;font-weight:700;">Walk to the ⚔️ trainer tile to battle Trainer Rex!</div>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>

  <div class="card">
    <?php
    // --- ZONE 0: CHOOSE STARTER SCREEN ---
    if ($_SESSION['zone'] == 0) {
    ?>
      <h2>Choose Your Starter</h2>
      <p>Pick the creature that will join you on your journey.</p>

      <form action="game.php" method="POST">
        <div class="starter-grid">
          <div class="starter-option">
            <input type="radio" id="grass" name="starter" value="grass" required>
            <label class="starter-card" for="grass">
              <span class="starter-emoji">🌱</span>
              <span class="starter-name">Grass</span>
            </label>
          </div>
          <div class="starter-option">
            <input type="radio" id="water" name="starter" value="water">
            <label class="starter-card" for="water">
              <span class="starter-emoji">💧</span>
              <span class="starter-name">Water</span>
            </label>
          </div>
          <div class="starter-option">
            <input type="radio" id="fire" name="starter" value="fire">
            <label class="starter-card" for="fire">
              <span class="starter-emoji">🔥</span>
              <span class="starter-name">Fire</span>
            </label>
          </div>
        </div>
        <button class="btn btn-primary" type="submit" style="width:100%;">Confirm Starter →</button>
      </form>

    <?php
    }
    // --- RIVAL DIALOGUE ---
    elseif ($_SESSION['zone'] == 'rival_dialogue') {
    ?>
      <h2>Watch Out! ⚠️</h2>
      <div class="dialogue-box">
        "Hey, <strong><?php echo htmlspecialchars($_SESSION['playerName']); ?></strong>! I see you just got your first creature. Let's see who is stronger!"
      </div>
      <form action="game.php" method="POST">
        <input type="hidden" name="start_rival_battle" value="true">
        <button class="btn btn-danger" type="submit" style="width:100%;">⚔️ Accept Battle!</button>
      </form>

    <?php
    }
    // --- RIVAL BATTLE ---
    elseif ($_SESSION['zone'] == 'rival_battle') {

        $myCreature = null;
        $rivalCreature = null;

        foreach ($creaturesData['creatures'] as $creature) {
            if ($creature['category'] == 'starter' && strtolower($creature['type']) == $_SESSION['starter']) {
                $myCreature = $creature;
            }
            if ($creature['category'] == 'rival') {
                $rivalCreature = $creature;
            }
        }

        $myHpPct   = max(0, round(($_SESSION['my_hp'] / $_SESSION['my_max_hp']) * 100));
        $rivHpPct  = max(0, round(($_SESSION['rival_hp'] / $_SESSION['rival_max_hp']) * 100));
    ?>
      <h2>Rival Battle!</h2>

      <div class="battle-msg"><?php echo htmlspecialchars($_SESSION['battle_message']); ?></div>

      <div class="battle-arena">
        <div class="creature-card mine">
          <h3>Your<br><?php echo htmlspecialchars($myCreature['name']); ?></h3>
          <div class="hp-text"><?php echo $_SESSION['my_hp']; ?> / <?php echo $_SESSION['my_max_hp']; ?> HP</div>
          <div class="hp-bar-wrap">
            <div class="hp-bar mine" style="width:<?php echo $myHpPct; ?>%"></div>
          </div>
        </div>

        <div class="vs-badge">VS</div>

        <div class="creature-card rival">
          <h3>Rival<br><?php echo htmlspecialchars($rivalCreature['name']); ?></h3>
          <div class="hp-text"><?php echo $_SESSION['rival_hp']; ?> / <?php echo $_SESSION['rival_max_hp']; ?> HP</div>
          <div class="hp-bar-wrap">
            <div class="hp-bar rival" style="width:<?php echo $rivHpPct; ?>%"></div>
          </div>
        </div>
      </div>

      <?php if ($_SESSION['rival_hp'] <= 0) { ?>

        <form action="game.php" method="POST">
          <input type="hidden" name="finish_rival_battle" value="true">
          <button class="btn btn-success" type="submit" style="width:100%;">🏆 Victory! Enter Zone 1</button>
        </form>

      <?php } elseif ($_SESSION['my_hp'] <= 0) { ?>

        <form action="game.php" method="POST">
          <input type="hidden" name="restart" value="true">
          <button class="btn btn-danger" type="submit" style="width:100%;">💀 Game Over — Restart</button>
        </form>

      <?php } else {
        $rivalActiveType = $_SESSION['my_type'] ?? ($_SESSION['team'][0]['type'] ?? 'Grass');
        $rivalTypeMoves  = getMovesForType($rivalActiveType);
        $teMap = ['Bug'=>'🐛','Flying'=>'🦅','Fire'=>'🔥','Water'=>'💧','Grass'=>'🌿','Electric'=>'⚡','Normal'=>'🐾','Rock'=>'🪨'];
      ?>

        <div style="display:flex;gap:10px;margin-bottom:10px;">
          <!-- Attack Trigger -->
          <button class="attack-trigger-btn" style="flex:1;" onclick="document.getElementById('attackMenu').classList.add('open')">
            ⚔️ Attack
          </button>
          <?php if (count($_SESSION['team'] ?? []) > 1): ?>
          <button class="btn btn-ghost" style="flex:1;" onclick="document.getElementById('rivalSwitchOverlay').classList.add('open')">
            🔄 Switch
          </button>
          <?php endif; ?>
        </div>

        <!-- TYPE-BASED Attack Menu -->
        <div class="attack-menu-overlay" id="attackMenu">
          <div class="attack-menu">
            <h3><?php echo $teMap[$rivalActiveType] ?? '⚔️'; ?> <?php echo $rivalActiveType; ?> Moves</h3>
            <div class="moves-grid">
              <?php foreach ($rivalTypeMoves as $key => $m): ?>
                <form action="game.php" method="POST">
                  <input type="hidden" name="attack_rival" value="true">
                  <input type="hidden" name="move" value="<?php echo htmlspecialchars($key); ?>">
                  <button class="move-btn" type="submit">
                    <span class="move-emoji"><?php echo $m['emoji']; ?></span>
                    <span class="move-name"><?php echo htmlspecialchars($m['name']); ?></span>
                    <span class="move-desc"><?php echo htmlspecialchars($m['desc']); ?></span>
                    <div class="move-power">Power: <?php echo $m['power']; ?></div>
                  </button>
                </form>
              <?php endforeach; ?>
            </div>
            <button class="cancel-btn" onclick="document.getElementById('attackMenu').classList.remove('open')">Cancel</button>
          </div>
        </div>

        <!-- SWITCH overlay for rival battle -->
        <?php if (count($_SESSION['team'] ?? []) > 1): ?>
        <div id="rivalSwitchOverlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.7);backdrop-filter:blur(4px);z-index:100;align-items:flex-end;justify-content:center;padding:20px;">
          <div class="attack-menu" style="max-width:500px;">
            <h3>🔄 Switch Creature</h3>
            <p style="font-size:11px;color:#f59e0b;margin-bottom:14px;text-align:center;">⚠️ Sparkpup gets a free hit when you switch!</p>
            <div style="display:flex;flex-direction:column;gap:10px;margin-bottom:14px;">
              <?php foreach ($_SESSION['team'] as $si => $sm):
                if ($si === 0) continue;
                $se = $teMap[$sm['type']] ?? '❓';
                $shp = $sm['max_hp'] > 0 ? round($sm['hp']/$sm['max_hp']*100) : 0;
                $sc = $shp > 50 ? '#22c55e' : ($shp > 25 ? '#f59e0b' : '#ef4444');
                $fainted = $sm['hp'] <= 0;
              ?>
                <form action="game.php" method="POST" style="margin:0;">
                  <input type="hidden" name="switch_creature" value="true">
                  <input type="hidden" name="switch_idx" value="<?php echo $si; ?>">
                  <button type="submit" style="width:100%;background:rgba(255,255,255,<?php echo $fainted?'0.02':'0.05';?>);border:1.5px solid rgba(255,255,255,<?php echo $fainted?'0.06':'0.15';?>);border-radius:12px;padding:12px 14px;display:flex;align-items:center;gap:12px;cursor:<?php echo $fainted?'not-allowed':'pointer';?>;opacity:<?php echo $fainted?'0.4':'1';?>;" <?php if($fainted) echo 'disabled'; ?>>
                    <span style="font-size:28px;"><?php echo $se; ?></span>
                    <div style="flex:1;text-align:left;">
                      <div style="font-family:'Press Start 2P',monospace;font-size:9px;color:var(--text);margin-bottom:5px;"><?php echo htmlspecialchars($sm['name']); ?> <span style="color:var(--muted);">Lv.<?php echo $sm['level']??1; ?></span></div>
                      <div style="font-size:10px;color:var(--muted);margin-bottom:4px;"><?php echo $sm['type']; ?> — <?php echo $sm['hp']; ?>/<?php echo $sm['max_hp']; ?> HP</div>
                      <div style="background:rgba(255,255,255,0.07);border-radius:999px;height:5px;overflow:hidden;"><div style="height:100%;border-radius:999px;width:<?php echo $shp;?>%;background:<?php echo $sc;?>;"></div></div>
                    </div>
                    <?php echo $fainted ? '<span style="color:var(--red);font-size:12px;font-weight:700;">FAINTED</span>' : '<span style="color:var(--green);font-size:18px;">→</span>'; ?>
                  </button>
                </form>
              <?php endforeach; ?>
            </div>
            <button class="cancel-btn" onclick="document.getElementById('rivalSwitchOverlay').classList.remove('open')">Cancel</button>
          </div>
        </div>
        <style>#rivalSwitchOverlay.open { display: flex !important; }</style>
        <?php endif; ?>

      <?php } ?>


    <?php
    }
    // --- ZONE 1: 2D TOP-DOWN MAP ---
    elseif ($_SESSION['zone'] == 1) {
    ?>
      <style>
        #zone1-wrap {
          display: flex;
          flex-direction: column;
          align-items: center;
          gap: 12px;
        }
        #gameCanvas {
          border: 3px solid rgba(255,255,255,0.12);
          border-radius: 12px;
          image-rendering: pixelated;
          box-shadow: 0 0 40px rgba(34,197,94,0.15);
          display: block;
        }
        #zone-hud {
          width: 480px;
          display: flex;
          justify-content: space-between;
          align-items: center;
          font-size: 12px;
          color: var(--muted);
        }
        #zone-hud span strong { color: var(--green); }
        #controls-hint {
          font-size: 11px;
          color: var(--muted);
          text-align: center;
          opacity: 0.7;
        }
        #battle-flash {
          position: fixed;
          inset: 0;
          background: white;
          z-index: 999;
          opacity: 0;
          pointer-events: none;
          transition: opacity 0.08s;
        }
        #encounter-popup {
          position: fixed;
          inset: 0;
          display: none;
          align-items: center;
          justify-content: center;
          z-index: 998;
          background: rgba(0,0,0,0.7);
          backdrop-filter: blur(4px);
        }
        #encounter-popup.show { display: flex; }
        #encounter-box {
          background: #16162a;
          border: 2px solid var(--green);
          border-radius: 20px;
          padding: 32px 40px;
          text-align: center;
          animation: popIn 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
          box-shadow: 0 0 40px rgba(34,197,94,0.3);
        }
        @keyframes popIn {
          from { transform: scale(0.7); opacity: 0; }
          to   { transform: scale(1);   opacity: 1; }
        }
        #encounter-box h3 {
          font-family: 'Press Start 2P', monospace;
          font-size: 12px;
          color: var(--green);
          margin-bottom: 10px;
        }
        #encounter-box p {
          font-size: 14px;
          margin-bottom: 24px;
          color: var(--text);
        }
        .mob-sprite {
          font-size: 52px;
          margin-bottom: 16px;
          animation: bounce 0.5s infinite alternate;
          display: block;
        }
        @keyframes bounce {
          from { transform: translateY(0); }
          to   { transform: translateY(-8px); }
        }
      </style>

      <div id="zone1-wrap">
        <div id="zone-hud">
          <span>🗺️ <strong>Zone 1: Grasslands</strong></span>
          <span>Use <strong>Arrow Keys</strong> or <strong>WASD</strong> to move</span>
        </div>

        <canvas id="gameCanvas" width="480" height="480"></canvas>

        <div id="controls-hint">Walk into tall grass 🌿 to encounter wild creatures! <?php if ($_SESSION['trainer_unlocked'] ?? false): ?><span style="color:#f59e0b;font-weight:800;">⚔️ Walk to Trainer Rex at the top!</span><?php endif; ?></div>
      </div>

      <!-- Battle flash overlay -->
      <div id="battle-flash"></div>

      <!-- Hidden trainer encounter form -->
      <form id="trainer-form" action="game.php" method="POST" style="display:none;">
        <input type="hidden" name="trainer_encounter" value="true">
      </form>

      <!-- Encounter popup -->
      <div id="encounter-popup">
        <div id="encounter-box">
          <span class="mob-sprite" id="mob-emoji">❓</span>
          <h3 id="encounter-name">Wild Creature!</h3>
          <p id="encounter-type" style="font-size:11px;color:var(--accent2);font-weight:700;letter-spacing:1px;text-transform:uppercase;margin-bottom:6px;"></p>
          <p id="encounter-text">A wild creature appeared!</p>
          <div id="encounter-stats" style="display:flex;gap:14px;justify-content:center;margin-bottom:20px;font-size:12px;font-weight:700;color:var(--muted);"></div>
          <form action="game.php" method="POST">
            <input type="hidden" name="wild_encounter" value="true">
            <button class="btn btn-danger" type="submit">⚔️ Battle!</button>
          </form>
        </div>
      </div>

      <form id="encounter-form" action="game.php" method="POST" style="display:none;">
        <input type="hidden" name="wild_encounter" value="true">
      </form>

      <script>
      (function() {
        const TILE = 32;       // px per tile
        const COLS = 15;
        const ROWS = 15;
        const canvas = document.getElementById('gameCanvas');
        const ctx = canvas.getContext('2d');

        // Tile type constants
        const T = { PATH:0, GRASS:1, TALL:2, TREE:3, WATER:4, SAND:5, FLOWER:6, TRAINER:7 };

        // Colours for each tile type
        const TILE_COLOR = {
          [T.PATH]:    '#8B7355',
          [T.GRASS]:   '#4a7c40',
          [T.TALL]:    '#2d5a27',
          [T.TREE]:    '#1e3d1a',
          [T.WATER]:   '#1e6b8c',
          [T.SAND]:    '#c8a96e',
          [T.FLOWER]:  '#4a7c40',
          [T.TRAINER]: '#8B7355',
        };

        // Trainer tile position (centre of map, always visible)
        const TRAINER_X = 7, TRAINER_Y = 1;
        const trainerUnlocked = <?php echo json_encode((bool)($_SESSION['trainer_unlocked'] ?? false)); ?>;

        // Map layout (15x15) — trainer at [1][7]
        const MAP = [
          [3,3,3,3,3,3,3,3,3,3,3,3,3,3,3],
          [3,0,0,0,0,0,0,0,0,0,0,0,0,0,3],
          [3,0,2,2,2,1,1,1,1,1,2,2,2,0,3],
          [3,0,2,3,2,1,6,1,6,1,2,3,2,0,3],
          [3,0,2,2,2,1,1,1,1,1,2,2,2,0,3],
          [3,0,1,1,1,0,0,0,0,0,1,1,1,0,3],
          [3,0,1,6,1,0,5,5,5,0,1,6,1,0,3],
          [3,0,1,1,1,0,5,4,5,0,1,1,1,0,3],
          [3,0,1,6,1,0,5,5,5,0,1,6,1,0,3],
          [3,0,1,1,1,0,0,0,0,0,1,1,1,0,3],
          [3,0,2,2,2,1,1,1,1,1,2,2,2,0,3],
          [3,0,2,3,2,1,6,1,6,1,2,3,2,0,3],
          [3,0,2,2,2,1,1,1,1,1,2,2,2,0,3],
          [3,0,0,0,0,0,0,0,0,0,0,0,0,0,3],
          [3,3,3,3,3,3,3,3,3,3,3,3,3,3,3],
        ];

        // Player state
        let px = 7, py = 7;   // tile position
        let facing = 'down';  // for sprite direction
        let stepFrame = 0;    // walk animation frame (0 or 1)
        let moving = false;
        let moveTimer = 0;
        const MOVE_DELAY = 150; // ms between moves
        let lastMoveTime = 0;
        const keys = {};

        // Encounter control
        let inEncounter = false;

        const TYPE_EMOJI = {
          'Bug':'🐛','Flying':'🦅','Fire':'🔥','Water':'💧',
          'Grass':'🌿','Electric':'⚡','Normal':'🐾','Rock':'🪨',
          'Ghost':'👻','Psychic':'🔮'
        };

        const WILD_CREATURES = <?php
          $zone1 = array_values(array_filter($creaturesData['creatures'], function($c) {
            return $c['category'] === 'wild_zone1';
          }));
          echo json_encode($zone1);
        ?>;

        // Camera (canvas is 480px = 15 tiles, map is 15 tiles — no scrolling needed at this size)
        // If you want a bigger map, you'd add camera offset logic here.

        function isWalkable(x, y) {
          if (x < 0 || y < 0 || x >= COLS || y >= ROWS) return false;
          const t = MAP[y][x];
          return t !== T.TREE && t !== T.WATER;
        }

        function isGrass(x, y) {
          return MAP[y][x] === T.TALL;
        }

        // --- DRAWING ---
        function drawTile(x, y) {
          const t = MAP[y][x];
          const px2 = x * TILE, py2 = y * TILE;
          ctx.fillStyle = TILE_COLOR[t];
          ctx.fillRect(px2, py2, TILE, TILE);

          // Tile decorations
          if (t === T.GRASS) {
            // Subtle grass lines
            ctx.strokeStyle = 'rgba(0,0,0,0.12)';
            ctx.lineWidth = 1;
            ctx.beginPath();
            ctx.moveTo(px2+6, py2+TILE); ctx.lineTo(px2+6, py2+TILE-8);
            ctx.moveTo(px2+14, py2+TILE); ctx.lineTo(px2+14, py2+TILE-6);
            ctx.moveTo(px2+22, py2+TILE); ctx.lineTo(px2+22, py2+TILE-9);
            ctx.stroke();
          }

          if (t === T.TALL) {
            // Tall grass — darker blades
            ctx.fillStyle = '#1e4a1a';
            for (let i = 0; i < 5; i++) {
              const bx = px2 + 4 + i * 6;
              const h = 8 + (i % 3) * 3;
              ctx.fillRect(bx, py2 + TILE - h, 3, h);
            }
          }

          if (t === T.TREE) {
            // Tree trunk + canopy
            ctx.fillStyle = '#5c3a1a';
            ctx.fillRect(px2+12, py2+20, 8, 12);
            ctx.fillStyle = '#1a5c14';
            ctx.beginPath();
            ctx.arc(px2+16, py2+14, 13, 0, Math.PI*2);
            ctx.fill();
            ctx.fillStyle = '#2d8a25';
            ctx.beginPath();
            ctx.arc(px2+12, py2+16, 8, 0, Math.PI*2);
            ctx.fill();
          }

          if (t === T.WATER) {
            ctx.fillStyle = '#2596be';
            ctx.fillRect(px2, py2, TILE, TILE);
            // Animated shimmer
            const t2 = Date.now() / 800;
            ctx.fillStyle = 'rgba(255,255,255,0.12)';
            ctx.fillRect(px2+4 + Math.sin(t2)*3, py2+10, 10, 3);
            ctx.fillRect(px2+16 + Math.cos(t2)*2, py2+20, 8, 2);
          }

          if (t === T.SAND) {
            ctx.fillStyle = '#d4b483';
            ctx.fillRect(px2, py2, TILE, TILE);
          }

          if (t === T.FLOWER) {
            ctx.fillStyle = '#4a7c40';
            ctx.fillRect(px2, py2, TILE, TILE);
            // Little flower
            ctx.fillStyle = '#f9d71c';
            ctx.beginPath();
            ctx.arc(px2+16, py2+16, 4, 0, Math.PI*2);
            ctx.fill();
            ctx.fillStyle = '#ff6b9d';
            for (let a = 0; a < 5; a++) {
              const angle = (a / 5) * Math.PI * 2;
              ctx.beginPath();
              ctx.arc(px2+16+Math.cos(angle)*6, py2+16+Math.sin(angle)*6, 3, 0, Math.PI*2);
              ctx.fill();
            }
          }

          if (t === T.PATH) {
            // Subtle path texture
            ctx.fillStyle = 'rgba(0,0,0,0.05)';
            if ((x+y) % 2 === 0) ctx.fillRect(px2, py2, TILE, TILE);
          }

          // Grid lines (very subtle)
          ctx.strokeStyle = 'rgba(0,0,0,0.08)';
          ctx.lineWidth = 0.5;
          ctx.strokeRect(px2, py2, TILE, TILE);
        }

        function drawPlayer() {
          const x = px * TILE, y = py * TILE;
          const bob = (stepFrame === 1 && moving) ? 1 : 0;

          // Shadow
          ctx.fillStyle = 'rgba(0,0,0,0.3)';
          ctx.beginPath();
          ctx.ellipse(x+16, y+30+bob, 8, 3, 0, 0, Math.PI*2);
          ctx.fill();

          // Body
          ctx.fillStyle = '#3b82f6';
          ctx.fillRect(x+9, y+16+bob, 14, 12);

          // Head
          ctx.fillStyle = '#fcd5b0';
          ctx.beginPath();
          ctx.arc(x+16, y+12+bob, 8, 0, Math.PI*2);
          ctx.fill();

          // Eyes based on direction
          ctx.fillStyle = '#1e293b';
          if (facing === 'down') {
            ctx.fillRect(x+13, y+11+bob, 2, 2);
            ctx.fillRect(x+17, y+11+bob, 2, 2);
          } else if (facing === 'up') {
            // Back of head — hair
            ctx.fillStyle = '#92400e';
            ctx.fillRect(x+9, y+6+bob, 14, 6);
          } else if (facing === 'left') {
            ctx.fillRect(x+11, y+11+bob, 2, 2);
          } else if (facing === 'right') {
            ctx.fillRect(x+19, y+11+bob, 2, 2);
          }

          // Hat
          ctx.fillStyle = '#dc2626';
          ctx.fillRect(x+8, y+7+bob, 16, 4);
          ctx.fillRect(x+10, y+4+bob, 12, 4);

          // Legs
          ctx.fillStyle = '#1e3a5f';
          const legOff = moving && stepFrame === 1 ? 2 : 0;
          ctx.fillRect(x+10, y+28+bob, 5, 6+legOff);
          ctx.fillRect(x+17, y+28+bob, 5, 6-legOff);
        }

        function drawTrainer() {
          const tx = TRAINER_X * TILE, ty = TRAINER_Y * TILE;
          const pulse = Math.sin(Date.now() / 400) * 0.5 + 0.5;

          if (trainerUnlocked) {
            // Glowing aura when unlocked
            ctx.save();
            ctx.shadowColor = '#f59e0b';
            ctx.shadowBlur = 12 + pulse * 8;
            ctx.fillStyle = 'rgba(245,158,11,0.25)';
            ctx.beginPath();
            ctx.arc(tx+16, ty+16, 18, 0, Math.PI*2);
            ctx.fill();
            ctx.restore();
          }

          // Trainer body (purple coat)
          ctx.fillStyle = trainerUnlocked ? '#7c3aed' : '#4b5563';
          ctx.fillRect(tx+9, ty+16, 14, 12);

          // Head
          ctx.fillStyle = '#fcd5b0';
          ctx.beginPath();
          ctx.arc(tx+16, ty+12, 8, 0, Math.PI*2);
          ctx.fill();

          // Hair (dark)
          ctx.fillStyle = '#1e293b';
          ctx.fillRect(tx+9, ty+5, 14, 7);

          // Eyes
          ctx.fillStyle = '#1e293b';
          ctx.fillRect(tx+13, ty+11, 2, 2);
          ctx.fillRect(tx+17, ty+11, 2, 2);

          // Legs
          ctx.fillStyle = '#374151';
          ctx.fillRect(tx+10, ty+28, 5, 5);
          ctx.fillRect(tx+17, ty+28, 5, 5);

          // Label above NPC
          ctx.font = 'bold 9px sans-serif';
          ctx.textAlign = 'center';
          if (trainerUnlocked) {
            ctx.fillStyle = '#f59e0b';
            ctx.fillText('⚔ REX', tx+16, ty-4);
          } else {
            ctx.fillStyle = 'rgba(255,255,255,0.35)';
            ctx.fillText('REX', tx+16, ty-4);
          }
          ctx.textAlign = 'left';
        }

        function draw() {
          ctx.clearRect(0, 0, canvas.width, canvas.height);
          for (let y = 0; y < ROWS; y++) {
            for (let x = 0; x < COLS; x++) {
              drawTile(x, y);
            }
          }
          drawTrainer();
          drawPlayer();
        }

        // --- INPUT ---
        document.addEventListener('keydown', e => { keys[e.key] = true; });
        document.addEventListener('keyup',   e => { keys[e.key] = false; });

        // --- GAME LOOP ---
        function tryMove() {
          if (inEncounter) return;
          const now = Date.now();
          if (now - lastMoveTime < MOVE_DELAY) return;

          let nx = px, ny = py, moved = false;

          if (keys['ArrowUp']    || keys['w'] || keys['W']) { ny--; facing='up';    moved=true; }
          else if (keys['ArrowDown']  || keys['s'] || keys['S']) { ny++; facing='down';  moved=true; }
          else if (keys['ArrowLeft']  || keys['a'] || keys['A']) { nx--; facing='left';  moved=true; }
          else if (keys['ArrowRight'] || keys['d'] || keys['D']) { nx++; facing='right'; moved=true; }

          if (moved && isWalkable(nx, ny)) {
            // Check if stepping onto trainer
            if (nx === TRAINER_X && ny === TRAINER_Y) {
              if (trainerUnlocked) {
                inEncounter = true;
                document.getElementById('trainer-form').submit();
              }
              // If not unlocked, block movement onto trainer tile
              return;
            }
            px = nx; py = ny;
            stepFrame = 1 - stepFrame;
            lastMoveTime = now;
            moving = true;

            // Check for grass encounter
            if (isGrass(px, py) && Math.random() < 0.3) {
              triggerEncounter();
            }
          } else if (moved) {
            moving = false;
          }
        }

        function triggerEncounter() {
          inEncounter = true;
          // Flash effect
          const flash = document.getElementById('battle-flash');
          let flashes = 0;
          const flashInterval = setInterval(() => {
            flash.style.opacity = flashes % 2 === 0 ? '0.85' : '0';
            flashes++;
            if (flashes >= 6) {
              clearInterval(flashInterval);
              flash.style.opacity = '0';
              showEncounterPopup();
            }
          }, 120);
        }

        function showEncounterPopup() {
          const creature = WILD_CREATURES[Math.floor(Math.random() * WILD_CREATURES.length)];
          const emoji = TYPE_EMOJI[creature.type] || '❓';
          document.getElementById('mob-emoji').textContent = emoji;
          document.getElementById('encounter-name').textContent = `Wild ${creature.name}!`;
          document.getElementById('encounter-type').textContent = creature.type + ' Type';
          document.getElementById('encounter-text').textContent =
            `A wild ${creature.name} leapt out from the tall grass!`;
          document.getElementById('encounter-stats').innerHTML =
            `<span>❤️ ${creature.hp}</span><span>⚔️ ${creature.attack}</span><span>🛡️ ${creature.defense}</span><span>💨 ${creature.speed}</span>`;
          document.getElementById('encounter-popup').classList.add('show');
        }

        function gameLoop() {
          tryMove();
          draw();
          requestAnimationFrame(gameLoop);
        }

        // Prevent arrow key scrolling
        window.addEventListener('keydown', e => {
          if (['ArrowUp','ArrowDown','ArrowLeft','ArrowRight'].includes(e.key)) e.preventDefault();
        });

        gameLoop();
      })();
      </script>

    <?php
    }
    // --- WILD BATTLE SCREEN ---
    elseif ($_SESSION['zone'] == 'battle') {

        $typeEmoji = [
            'Bug'=>'🐛','Flying'=>'🦅','Fire'=>'🔥','Water'=>'💧',
            'Grass'=>'🌿','Electric'=>'⚡','Normal'=>'🐾','Rock'=>'🪨',
            'Ghost'=>'👻','Psychic'=>'🔮','Ice'=>'❄️','Dragon'=>'🐲',
        ];
        $wildEmoji = $typeEmoji[$_SESSION['wild_type']] ?? '❓';

        $myHpPct   = isset($_SESSION['my_max_hp']) ? max(0, round($_SESSION['my_hp'] / $_SESSION['my_max_hp'] * 100)) : 100;
        $wildHpPct = max(0, round($_SESSION['wild_hp'] / $_SESSION['wild_max_hp'] * 100));

        // HP bar colour: green > yellow > red
        $myBarColor   = $myHpPct > 50 ? '#22c55e' : ($myHpPct > 25 ? '#f59e0b' : '#ef4444');
        $wildBarColor = $wildHpPct > 50 ? '#22c55e' : ($wildHpPct > 25 ? '#f59e0b' : '#ef4444');

        $battleOver  = $_SESSION['wild_hp'] <= 0 || ($_SESSION['my_hp'] ?? 1) <= 0 || !empty($_SESSION['wild_caught']);
        $caught      = !empty($_SESSION['wild_caught']);
        $myFainted   = ($_SESSION['my_hp'] ?? 1) <= 0;
        $wildFainted = $_SESSION['wild_hp'] <= 0;

        // Active creature from team slot 0
        $activeCreature = !empty($_SESSION['team']) ? $_SESSION['team'][0] : null;
        $myCreatureName = $activeCreature['name'] ?? 'Your Creature';
        $starterEmoji   = ['Grass'=>'🌱','Water'=>'💧','Fire'=>'🔥','Electric'=>'⚡','Bug'=>'🐛','Flying'=>'🦅'][$activeCreature['type'] ?? 'Grass'] ?? '🌱';
    ?>
      <style>
        .wild-arena {
          display: grid;
          grid-template-columns: 1fr auto 1fr;
          align-items: center;
          gap: 10px;
          margin: 18px 0;
        }
        .wild-card {
          border-radius: 14px;
          padding: 14px 16px;
          border: 2px solid;
        }
        .wild-card.mine  { border-color: rgba(6,182,212,0.4); background: rgba(6,182,212,0.06); }
        .wild-card.enemy { border-color: rgba(34,197,94,0.4); background: rgba(34,197,94,0.06); }
        .wild-card h3 {
          font-family: 'Press Start 2P', monospace;
          font-size: 8px;
          margin-bottom: 10px;
          line-height: 1.6;
        }
        .wild-card.mine  h3 { color: var(--player-color); }
        .wild-card.enemy h3 { color: var(--green); }
        .wild-sprite { font-size: 38px; display: block; text-align: center; margin-bottom: 6px; }
        .hp-text  { font-size: 11px; font-weight: 700; color: var(--muted); margin-bottom: 5px; }
        .hp-track { background: rgba(255,255,255,0.08); border-radius: 999px; height: 9px; overflow: hidden; }
        .hp-fill  { height: 100%; border-radius: 999px; transition: width 0.4s ease; }
        .type-badge {
          display: inline-block;
          font-size: 9px;
          font-weight: 800;
          text-transform: uppercase;
          letter-spacing: 1px;
          padding: 2px 8px;
          border-radius: 999px;
          background: rgba(255,255,255,0.08);
          color: var(--accent2);
          margin-top: 6px;
        }
        .balls-row {
          display: flex;
          gap: 6px;
          align-items: center;
          font-size: 13px;
          font-weight: 700;
          color: var(--muted);
          margin-bottom: 14px;
        }
        .ball-icon { font-size: 18px; }
        .action-grid {
          display: grid;
          grid-template-columns: 1fr 1fr;
          gap: 10px;
          margin-top: 4px;
        }
        .btn-ball {
          background: linear-gradient(135deg, rgba(251,191,36,0.2), rgba(217,119,6,0.2));
          border: 2px solid #f59e0b;
          color: #fbbf24;
          padding: 12px 10px;
          border-radius: 12px;
          font-family: 'Press Start 2P', monospace;
          font-size: 9px;
          cursor: pointer;
          transition: all 0.2s;
          letter-spacing: 0.5px;
        }
        .btn-ball:hover { background: rgba(251,191,36,0.25); transform: translateY(-2px); }
        .btn-ball:disabled { opacity: 0.4; cursor: not-allowed; transform: none; }

        /* Attack sub-menu overlay (reusing existing attack-menu styles) */
        #wild-attack-overlay {
          display: none;
          position: fixed;
          inset: 0;
          background: rgba(0,0,0,0.7);
          backdrop-filter: blur(4px);
          z-index: 100;
          align-items: flex-end;
          justify-content: center;
          padding: 20px;
        }
        #wild-attack-overlay.open { display: flex; }

        /* Team panel */
        .team-section {
          margin-top: 20px;
          border-top: 1px solid var(--border);
          padding-top: 16px;
        }
        .team-label {
          font-family: 'Press Start 2P', monospace;
          font-size: 9px;
          color: var(--gold);
          margin-bottom: 12px;
        }
        .team-grid {
          display: grid;
          grid-template-columns: repeat(3, 1fr);
          gap: 8px;
        }
        .team-slot {
          background: rgba(255,255,255,0.03);
          border: 1px solid var(--border);
          border-radius: 10px;
          padding: 10px 8px;
          text-align: center;
        }
        .team-slot .t-emoji { font-size: 22px; }
        .team-slot .t-name  { font-size: 9px; font-weight: 800; color: var(--text); margin-top: 4px; display: block; }
        .team-slot .t-type  { font-size: 9px; color: var(--muted); }
        .team-empty { opacity: 0.3; border-style: dashed; }
      </style>

      <h2>Wild Battle! <?php echo $wildEmoji; ?></h2>

      <div class="battle-msg"><?php echo htmlspecialchars($_SESSION['wild_battle_msg']); ?></div>

      <div class="wild-arena">
        <!-- My creature -->
        <div class="wild-card mine">
          <span class="wild-sprite"><?php echo $starterEmoji; ?></span>
          <h3><?php echo htmlspecialchars($myCreatureName); ?></h3>
          <div class="hp-text"><?php echo $_SESSION['my_hp'] ?? '?'; ?> / <?php echo $_SESSION['my_max_hp'] ?? '?'; ?> HP</div>
          <div class="hp-track"><div class="hp-fill" style="width:<?php echo $myHpPct; ?>%;background:<?php echo $myBarColor; ?>;"></div></div>
        </div>

        <div class="vs-badge">VS</div>

        <!-- Wild creature -->
        <div class="wild-card enemy">
          <span class="wild-sprite"><?php echo $wildEmoji; ?></span>
          <h3><?php echo htmlspecialchars($_SESSION['wild_name']); ?></h3>
          <div class="hp-text"><?php echo $_SESSION['wild_hp']; ?> / <?php echo $_SESSION['wild_max_hp']; ?> HP</div>
          <div class="hp-track"><div class="hp-fill" style="width:<?php echo $wildHpPct; ?>%;background:<?php echo $wildBarColor; ?>;"></div></div>
          <span class="type-badge"><?php echo htmlspecialchars($_SESSION['wild_type']); ?></span>
        </div>
      </div>

      <?php
      // Get active creature's type-specific moves
      $activeType  = $_SESSION['my_type'] ?? ($_SESSION['team'][0]['type'] ?? 'Grass');
      $typeMoves   = getMovesForType($activeType);
      $typeEmojiMap2 = ['Bug'=>'🐛','Flying'=>'🦅','Fire'=>'🔥','Water'=>'💧','Grass'=>'🌿','Electric'=>'⚡','Normal'=>'🐾','Rock'=>'🪨','Ghost'=>'👻','Psychic'=>'🔮'];
      ?>

      <?php if (!$battleOver): ?>
        <!-- Balls row -->
        <div class="balls-row">
          <span class="ball-icon">🟡</span>
          <span><?php echo $_SESSION['balls'] ?? 0; ?> balls remaining</span>
          <span style="margin-left:auto;font-size:10px;color:var(--accent2);">Active: <strong><?php echo $_SESSION['team'][0]['name']; ?></strong> (<?php echo $activeType; ?>)</span>
        </div>

        <!-- 4 action buttons -->
        <div class="action-grid">
          <button class="attack-trigger-btn" onclick="document.getElementById('wild-attack-overlay').classList.add('open')" style="grid-column:1/2;">
            ⚔️ Attack
          </button>
          <form action="game.php" method="POST" style="margin:0;">
            <input type="hidden" name="throw_ball" value="true">
            <button class="btn-ball" type="submit" style="width:100%;height:100%;" <?php if (($_SESSION['balls'] ?? 0) <= 0) echo 'disabled'; ?>>
              🟡 Throw Ball
            </button>
          </form>
          <?php if (count($_SESSION['team'] ?? []) > 1): ?>
          <button class="btn btn-ghost" onclick="document.getElementById('switch-overlay').classList.add('open')" style="grid-column:1/2;">
            🔄 Switch
          </button>
          <?php endif; ?>
          <form action="game.php" method="POST" style="margin:0;<?php echo count($_SESSION['team'] ?? []) > 1 ? '' : 'grid-column:1/-1;'; ?>">
            <input type="hidden" name="run_away" value="true">
            <button class="btn btn-ghost" type="submit" style="width:100%;">🏃 Run Away</button>
          </form>
        </div>

        <!-- TYPE-BASED move overlay -->
        <div id="wild-attack-overlay">
          <div class="attack-menu">
            <h3><?php echo $typeEmojiMap2[$activeType] ?? '⚔️'; ?> <?php echo $activeType; ?> Moves</h3>
            <div class="moves-grid">
              <?php foreach ($typeMoves as $key => $m): ?>
                <form action="game.php" method="POST">
                  <input type="hidden" name="wild_attack" value="true">
                  <input type="hidden" name="move" value="<?php echo htmlspecialchars($key); ?>">
                  <button class="move-btn" type="submit">
                    <span class="move-emoji"><?php echo $m['emoji']; ?></span>
                    <span class="move-name"><?php echo htmlspecialchars($m['name']); ?></span>
                    <span class="move-desc"><?php echo htmlspecialchars($m['desc']); ?></span>
                    <div class="move-power">Power: <?php echo $m['power']; ?></div>
                  </button>
                </form>
              <?php endforeach; ?>
            </div>
            <button class="cancel-btn" onclick="document.getElementById('wild-attack-overlay').classList.remove('open')">Cancel</button>
          </div>
        </div>

        <!-- SWITCH CREATURE overlay -->
        <?php if (count($_SESSION['team'] ?? []) > 1): ?>
        <div id="switch-overlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.7);backdrop-filter:blur(4px);z-index:100;align-items:flex-end;justify-content:center;padding:20px;">
          <div class="attack-menu" style="max-width:500px;">
            <h3>🔄 Switch Creature</h3>
            <p style="font-size:11px;color:#f59e0b;margin-bottom:14px;text-align:center;">⚠️ Enemy gets a free hit when you switch!</p>
            <div style="display:flex;flex-direction:column;gap:10px;margin-bottom:14px;">
              <?php foreach ($_SESSION['team'] as $si => $sm):
                if ($si === 0) continue; // skip current active
                $se = $typeEmojiMap2[$sm['type']] ?? '❓';
                $shp = $sm['max_hp'] > 0 ? round($sm['hp']/$sm['max_hp']*100) : 0;
                $shpColor = $shp > 50 ? '#22c55e' : ($shp > 25 ? '#f59e0b' : '#ef4444');
                $fainted = $sm['hp'] <= 0;
              ?>
                <form action="game.php" method="POST" style="margin:0;">
                  <input type="hidden" name="switch_creature" value="true">
                  <input type="hidden" name="switch_idx" value="<?php echo $si; ?>">
                  <button type="submit" style="width:100%;background:rgba(255,255,255,<?php echo $fainted ? '0.02' : '0.05'; ?>);border:1.5px solid <?php echo $fainted ? 'rgba(255,255,255,0.06)' : 'rgba(255,255,255,0.15)'; ?>;border-radius:12px;padding:12px 14px;display:flex;align-items:center;gap:12px;cursor:<?php echo $fainted ? 'not-allowed' : 'pointer'; ?>;opacity:<?php echo $fainted ? '0.4' : '1'; ?>;" <?php if ($fainted) echo 'disabled'; ?>>
                    <span style="font-size:28px;"><?php echo $se; ?></span>
                    <div style="flex:1;text-align:left;">
                      <div style="font-family:'Press Start 2P',monospace;font-size:9px;color:var(--text);margin-bottom:5px;"><?php echo htmlspecialchars($sm['name']); ?> <span style="color:var(--muted);">Lv.<?php echo $sm['level'] ?? 1; ?></span></div>
                      <div style="font-size:10px;color:var(--muted);margin-bottom:4px;"><?php echo htmlspecialchars($sm['type']); ?> — HP: <?php echo $sm['hp']; ?>/<?php echo $sm['max_hp']; ?></div>
                      <div style="background:rgba(255,255,255,0.07);border-radius:999px;height:5px;overflow:hidden;">
                        <div style="height:100%;border-radius:999px;width:<?php echo $shp; ?>%;background:<?php echo $shpColor; ?>;"></div>
                      </div>
                    </div>
                    <?php if (!$fainted): ?><span style="color:var(--green);font-size:18px;">→</span><?php else: ?><span style="color:var(--red);font-size:12px;font-weight:700;">FAINTED</span><?php endif; ?>
                  </button>
                </form>
              <?php endforeach; ?>
            </div>
            <button class="cancel-btn" onclick="document.getElementById('switch-overlay').classList.remove('open')">Cancel</button>
          </div>
        </div>
        <style>#switch-overlay.open { display: flex !important; }</style>
        <?php endif; ?>

      <?php elseif ($caught): ?>
        <div style="text-align:center;padding:16px 0;">
          <div style="font-size:52px;margin-bottom:10px;">🎉</div>
          <p style="color:var(--green);font-weight:800;font-size:16px;margin-bottom:6px;"><?php echo htmlspecialchars($_SESSION['wild_name']); ?> joined your team!</p>
        </div>
        <form action="game.php" method="POST">
          <input type="hidden" name="leave_battle" value="true">
          <button class="btn btn-success" type="submit" style="width:100%;">← Back to Map</button>
        </form>

      <?php elseif ($wildFainted): ?>
        <div style="text-align:center;padding:10px 0 16px;">
          <p style="color:var(--gold);font-weight:800;font-size:15px;">You won the battle!</p>
        </div>
        <form action="game.php" method="POST">
          <input type="hidden" name="leave_battle" value="true">
          <button class="btn btn-success" type="submit" style="width:100%;">← Back to Map</button>
        </form>

      <?php elseif ($myFainted): ?>
        <form action="game.php" method="POST">
          <input type="hidden" name="restart" value="true">
          <button class="btn btn-danger" type="submit" style="width:100%;">💀 Game Over — Restart</button>
        </form>
      <?php endif; ?>


      <!-- Team display (always visible during battle) -->
      <?php if (!empty($_SESSION['team'])): ?>
        <div class="team-section">
          <div class="team-label">🎒 Your Team (<?php echo count($_SESSION['team']); ?>/6)</div>
          <div class="team-grid">
            <?php
            $typeEmojiFull = ['Bug'=>'🐛','Flying'=>'🦅','Fire'=>'🔥','Water'=>'💧','Grass'=>'🌿','Electric'=>'⚡','Normal'=>'🐾','Rock'=>'🪨','Ghost'=>'👻','Psychic'=>'🔮'];
            foreach ($_SESSION['team'] as $tm):
              $te = $typeEmojiFull[$tm['type']] ?? '❓';
            ?>
              <div class="team-slot">
                <div class="t-emoji"><?php echo $te; ?></div>
                <span class="t-name"><?php echo htmlspecialchars($tm['name']); ?></span>
                <div class="t-type"><?php echo htmlspecialchars($tm['type']); ?></div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>

    <?php } ?>

    <?php
    // --- TEAM SCREEN ---
    if (($_SESSION['zone'] ?? '') === 'team'):
        $typeEmojiMap = ['Bug'=>'🐛','Flying'=>'🦅','Fire'=>'🔥','Water'=>'💧','Grass'=>'🌿',
                         'Electric'=>'⚡','Normal'=>'🐾','Rock'=>'🪨','Ghost'=>'👻','Psychic'=>'🔮'];
    ?>
    <style>
      .team-screen-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 22px;
      }
      .team-screen-header h2 { margin-bottom: 0; }

      .team-card-full {
        background: rgba(255,255,255,0.03);
        border: 1px solid var(--border);
        border-radius: 16px;
        padding: 18px 20px;
        margin-bottom: 14px;
        position: relative;
        overflow: hidden;
        transition: border-color 0.2s;
      }
      .team-card-full:first-of-type {
        border-color: rgba(251,191,36,0.4);
        background: rgba(251,191,36,0.04);
      }
      .active-badge {
        position: absolute;
        top: 12px; right: 14px;
        font-size: 9px;
        font-family: 'Press Start 2P', monospace;
        color: #fbbf24;
        background: rgba(251,191,36,0.15);
        border: 1px solid rgba(251,191,36,0.3);
        border-radius: 999px;
        padding: 3px 9px;
      }

      .tc-top {
        display: flex;
        align-items: center;
        gap: 14px;
        margin-bottom: 14px;
      }
      .tc-sprite { font-size: 42px; line-height: 1; }
      .tc-info   { flex: 1; }
      .tc-name {
        font-family: 'Press Start 2P', monospace;
        font-size: 10px;
        color: var(--text);
        margin-bottom: 4px;
      }
      .tc-meta {
        font-size: 12px;
        color: var(--muted);
        font-weight: 700;
      }
      .tc-level {
        font-family: 'Press Start 2P', monospace;
        font-size: 11px;
        color: var(--gold);
      }

      /* XP bar */
      .xp-row { margin-bottom: 12px; }
      .xp-label {
        display: flex;
        justify-content: space-between;
        font-size: 11px;
        font-weight: 800;
        color: var(--muted);
        margin-bottom: 4px;
      }
      .xp-label span:first-child { color: var(--accent2); }
      .xp-track {
        background: rgba(255,255,255,0.07);
        border-radius: 999px;
        height: 8px;
        overflow: hidden;
      }
      .xp-fill {
        height: 100%;
        border-radius: 999px;
        background: linear-gradient(90deg, #06b6d4, #7c3aed);
        transition: width 0.5s ease;
      }

      /* Stats grid */
      .tc-stats {
        display: grid;
        grid-template-columns: repeat(4,1fr);
        gap: 8px;
      }
      .stat-box {
        background: rgba(255,255,255,0.04);
        border-radius: 10px;
        padding: 8px 6px;
        text-align: center;
      }
      .stat-val {
        font-family: 'Press Start 2P', monospace;
        font-size: 11px;
        color: var(--text);
        display: block;
        margin-bottom: 3px;
      }
      .stat-lbl {
        font-size: 9px;
        color: var(--muted);
        text-transform: uppercase;
        letter-spacing: 0.5px;
      }

      /* HP bar in team card */
      .tc-hp-row { margin-bottom: 10px; }
      .tc-hp-label {
        display: flex;
        justify-content: space-between;
        font-size: 11px;
        font-weight: 700;
        color: var(--muted);
        margin-bottom: 4px;
      }
      .tc-hp-track { background:rgba(255,255,255,0.07); border-radius:999px; height:8px; overflow:hidden; }
      .tc-hp-fill  { height:100%; border-radius:999px; transition: width 0.4s; }

      .empty-team {
        text-align: center;
        padding: 40px 0;
        color: var(--muted);
        font-size: 14px;
      }
      .empty-team .big { font-size: 48px; margin-bottom: 12px; }
    </style>

    <div class="team-screen-header">
      <h2>🎒 Your Team</h2>
      <form action="game.php" method="POST" style="margin:0;">
        <input type="hidden" name="close_team" value="true">
        <button class="btn btn-ghost" type="submit" style="padding:8px 18px;font-size:12px;">← Back</button>
      </form>
    </div>

    <?php if (empty($_SESSION['team'])): ?>
      <div class="empty-team">
        <div class="big">🎒</div>
        <p>Your team is empty. Go catch some creatures!</p>
      </div>
    <?php else: ?>

      <?php foreach ($_SESSION['team'] as $i => $tm):
          $te      = $typeEmojiMap[$tm['type']] ?? '❓';
          $lvl     = $tm['level']   ?? 1;
          $xp      = $tm['xp']      ?? 0;
          $xpNext  = $tm['xp_next'] ?? xpForLevel($lvl);
          $xpPct   = $xpNext > 0 ? min(100, round($xp / $xpNext * 100)) : 100;
          $hpPct   = $tm['max_hp'] > 0 ? min(100, round($tm['hp'] / $tm['max_hp'] * 100)) : 100;
          $hpColor = $hpPct > 50 ? '#22c55e' : ($hpPct > 25 ? '#f59e0b' : '#ef4444');
      ?>
        <div class="team-card-full">
          <?php if ($i === 0): ?>
            <span class="active-badge">★ ACTIVE</span>
          <?php endif; ?>

          <div class="tc-top">
            <span class="tc-sprite"><?php echo $te; ?></span>
            <div class="tc-info">
              <div class="tc-name"><?php echo htmlspecialchars($tm['name']); ?></div>
              <div class="tc-meta"><?php echo htmlspecialchars($tm['type']); ?> type</div>
            </div>
            <div class="tc-level">Lv.<?php echo $lvl; ?></div>
          </div>

          <!-- HP bar -->
          <div class="tc-hp-row">
            <div class="tc-hp-label">
              <span>❤️ HP</span>
              <span><?php echo $tm['hp']; ?> / <?php echo $tm['max_hp']; ?></span>
            </div>
            <div class="tc-hp-track">
              <div class="tc-hp-fill" style="width:<?php echo $hpPct; ?>%;background:<?php echo $hpColor; ?>;"></div>
            </div>
          </div>

          <!-- XP bar -->
          <div class="xp-row">
            <div class="xp-label">
              <span>✨ XP</span>
              <span><?php echo $xp; ?> / <?php echo $xpNext; ?> to Lv.<?php echo $lvl+1; ?></span>
            </div>
            <div class="xp-track">
              <div class="xp-fill" style="width:<?php echo $xpPct; ?>%;"></div>
            </div>
          </div>

          <!-- Stats -->
          <div class="tc-stats">
            <div class="stat-box">
              <span class="stat-val"><?php echo $tm['attack']; ?></span>
              <span class="stat-lbl">⚔️ Atk</span>
            </div>
            <div class="stat-box">
              <span class="stat-val"><?php echo $tm['defense']; ?></span>
              <span class="stat-lbl">🛡️ Def</span>
            </div>
            <div class="stat-box">
              <span class="stat-val"><?php echo $tm['speed']; ?></span>
              <span class="stat-lbl">💨 Spd</span>
            </div>
            <div class="stat-box">
              <span class="stat-val"><?php echo $tm['max_hp']; ?></span>
              <span class="stat-lbl">❤️ MaxHP</span>
            </div>
          </div>
        </div>
      <?php endforeach; ?>

      <!-- Empty slots -->
      <?php for ($s = count($_SESSION['team']); $s < 6; $s++): ?>
        <div class="team-card-full team-empty" style="opacity:0.3;border-style:dashed;text-align:center;padding:16px;color:var(--muted);font-size:13px;">
          Empty Slot <?php echo $s+1; ?>
        </div>
      <?php endfor; ?>

    <?php endif; ?>

    <?php endif; // end team screen ?>

    <?php
    // --- TRAINER BATTLE SCREEN ---
    if (($_SESSION['zone'] ?? '') === 'trainer_battle'):
        $typeEmojiTr = ['Bug'=>'🐛','Flying'=>'🦅','Fire'=>'🔥','Water'=>'💧','Grass'=>'🌿',
                        'Electric'=>'⚡','Normal'=>'🐾','Rock'=>'🪨','Ghost'=>'👻','Psychic'=>'🔮'];
        $myHpPct     = isset($_SESSION['my_max_hp']) && $_SESSION['my_max_hp'] > 0
                        ? max(0, round($_SESSION['my_hp'] / $_SESSION['my_max_hp'] * 100)) : 100;
        $trHpPct     = max(0, round($_SESSION['trainer_hp'] / $_SESSION['trainer_max_hp'] * 100));
        $myBarColor  = $myHpPct > 50 ? '#22c55e' : ($myHpPct > 25 ? '#f59e0b' : '#ef4444');
        $trBarColor  = $trHpPct > 50 ? '#22c55e' : ($trHpPct > 25 ? '#f59e0b' : '#ef4444');
        $trDefeated  = $_SESSION['trainer_defeated'] ?? false;
        $myFainted   = ($_SESSION['my_hp'] ?? 1) <= 0;

        $trActiveCreature = !empty($_SESSION['team']) ? $_SESSION['team'][0] : null;
        $starterEmoji = ['Grass'=>'🌱','Water'=>'💧','Fire'=>'🔥','Electric'=>'⚡','Bug'=>'🐛','Flying'=>'🦅'][$trActiveCreature['type'] ?? 'Grass'] ?? '🌱';
        $myName = $trActiveCreature['name'] ?? 'Your Creature';
    ?>
      <h2>⚔️ Trainer Battle!</h2>

      <?php
        $trName     = $_SESSION['trainer_name']     ?? 'Trainer';
        $trCreature = $_SESSION['trainer_creature'] ?? 'Emberfox';
        $trEmoji    = $_SESSION['trainer_emoji']    ?? '🔥';
        $mapZone    = $_SESSION['last_map_zone']    ?? 1;
        $nextZone   = $mapZone + 1;
        $dialogues  = [
            'normal'  => "\"You won't pass through here so easily!\"",
            'won'     => "\"...I can't believe I lost. Zone $nextZone is yours!\"",
            'lost'    => "\"Ha! Your creature couldn't handle my $trCreature!\"",
        ];
      ?>

      <!-- Trainer speech bubble -->
      <div class="dialogue-box" style="margin-bottom:16px;border-left:3px solid #7c3aed;">
        <strong style="color:#a78bfa;"><?php echo htmlspecialchars($trName); ?>:</strong>
        <?php
          if ($trDefeated)  echo $dialogues['won'];
          elseif ($myFainted) echo $dialogues['lost'];
          else echo $dialogues['normal'];
        ?>
      </div>

      <div class="battle-msg" style="border-left-color:#a78bfa;"><?php echo htmlspecialchars($_SESSION['trainer_battle_msg']); ?></div>

      <div class="wild-arena">
        <div class="wild-card mine">
          <span class="wild-sprite"><?php echo $starterEmoji; ?></span>
          <h3><?php echo htmlspecialchars($myName); ?></h3>
          <div class="hp-text"><?php echo $_SESSION['my_hp']; ?> / <?php echo $_SESSION['my_max_hp']; ?> HP</div>
          <div class="hp-track"><div class="hp-fill" style="width:<?php echo $myHpPct; ?>%;background:<?php echo $myBarColor; ?>;"></div></div>
        </div>

        <div class="vs-badge">VS</div>

        <div class="wild-card enemy" style="border-color:rgba(167,139,250,0.4);background:rgba(124,58,237,0.06);">
          <span class="wild-sprite"><?php echo $trEmoji; ?></span>
          <h3 style="color:#a78bfa;"><?php echo htmlspecialchars($trCreature); ?></h3>
          <div class="hp-text"><?php echo $_SESSION['trainer_hp']; ?> / <?php echo $_SESSION['trainer_max_hp']; ?> HP</div>
          <div class="hp-track"><div class="hp-fill" style="width:<?php echo $trHpPct; ?>%;background:<?php echo $trBarColor; ?>;"></div></div>
          <span class="type-badge" style="color:#a78bfa;"><?php echo htmlspecialchars($trName); ?>'s creature</span>
        </div>
      </div>

      <?php if ($trDefeated): ?>
        <div style="text-align:center;padding:14px 0;">
          <div style="font-size:44px;margin-bottom:8px;">🏆</div>
          <p style="color:var(--gold);font-weight:800;font-size:15px;">You defeated <?php echo htmlspecialchars($trName); ?>!</p>
          <p style="font-size:13px;margin-top:6px;">Zone <?php echo $nextZone; ?> is now unlocked! +5 bonus balls!</p>
        </div>
        <form action="game.php" method="POST">
          <input type="hidden" name="advance_zone" value="true">
          <button class="btn btn-success" type="submit" style="width:100%;margin-top:10px;">🗺️ Enter Zone <?php echo $nextZone; ?> →</button>
        </form>

      <?php elseif ($myFainted): ?>
        <form action="game.php" method="POST">
          <input type="hidden" name="restart" value="true">
          <button class="btn btn-danger" type="submit" style="width:100%;">💀 Game Over — Restart</button>
        </form>

      <?php else:
        $trActiveType = $_SESSION['my_type'] ?? ($_SESSION['team'][0]['type'] ?? 'Grass');
        $trTypeMoves  = getMovesForType($trActiveType);
        $teMapTr = ['Bug'=>'🐛','Flying'=>'🦅','Fire'=>'🔥','Water'=>'💧','Grass'=>'🌿','Electric'=>'⚡','Normal'=>'🐾','Rock'=>'🪨'];
      ?>
        <!-- Action row: Attack + Switch -->
        <div style="display:flex;gap:10px;margin-bottom:10px;">
          <button class="attack-trigger-btn" style="flex:1;" onclick="document.getElementById('trainer-attack-overlay').classList.add('open')">
            ⚔️ Attack
          </button>
          <?php if (count($_SESSION['team'] ?? []) > 1): ?>
          <button class="btn btn-ghost" style="flex:1;" onclick="document.getElementById('trainerSwitchOverlay').classList.add('open')">
            🔄 Switch
          </button>
          <?php endif; ?>
        </div>

        <!-- TYPE-BASED trainer attack overlay -->
        <div id="trainer-attack-overlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.7);backdrop-filter:blur(4px);z-index:100;align-items:flex-end;justify-content:center;padding:20px;">
          <div class="attack-menu">
            <h3><?php echo $teMapTr[$trActiveType] ?? '⚔️'; ?> <?php echo $trActiveType; ?> Moves</h3>
            <div class="moves-grid">
              <?php foreach ($trTypeMoves as $key => $m): ?>
                <form action="game.php" method="POST">
                  <input type="hidden" name="attack_trainer" value="true">
                  <input type="hidden" name="move" value="<?php echo htmlspecialchars($key); ?>">
                  <button class="move-btn" type="submit">
                    <span class="move-emoji"><?php echo $m['emoji']; ?></span>
                    <span class="move-name"><?php echo htmlspecialchars($m['name']); ?></span>
                    <span class="move-desc"><?php echo htmlspecialchars($m['desc']); ?></span>
                    <div class="move-power">Power: <?php echo $m['power']; ?></div>
                  </button>
                </form>
              <?php endforeach; ?>
            </div>
            <button class="cancel-btn" onclick="document.getElementById('trainer-attack-overlay').classList.remove('open')">Cancel</button>
          </div>
        </div>
        <style>#trainer-attack-overlay.open { display: flex !important; }</style>

        <!-- SWITCH overlay for trainer battle -->
        <?php if (count($_SESSION['team'] ?? []) > 1): ?>
        <div id="trainerSwitchOverlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.7);backdrop-filter:blur(4px);z-index:100;align-items:flex-end;justify-content:center;padding:20px;">
          <div class="attack-menu" style="max-width:500px;">
            <h3>🔄 Switch Creature</h3>
            <p style="font-size:11px;color:#f59e0b;margin-bottom:14px;text-align:center;">⚠️ Emberfox gets a free hit when you switch!</p>
            <div style="display:flex;flex-direction:column;gap:10px;margin-bottom:14px;">
              <?php foreach ($_SESSION['team'] as $si => $sm):
                if ($si === 0) continue;
                $se = $teMapTr[$sm['type']] ?? '❓';
                $shp = $sm['max_hp'] > 0 ? round($sm['hp']/$sm['max_hp']*100) : 0;
                $sc = $shp > 50 ? '#22c55e' : ($shp > 25 ? '#f59e0b' : '#ef4444');
                $fainted = $sm['hp'] <= 0;
              ?>
                <form action="game.php" method="POST" style="margin:0;">
                  <input type="hidden" name="switch_creature" value="true">
                  <input type="hidden" name="switch_idx" value="<?php echo $si; ?>">
                  <button type="submit" style="width:100%;background:rgba(255,255,255,<?php echo $fainted?'0.02':'0.05';?>);border:1.5px solid rgba(255,255,255,<?php echo $fainted?'0.06':'0.15';?>);border-radius:12px;padding:12px 14px;display:flex;align-items:center;gap:12px;cursor:<?php echo $fainted?'not-allowed':'pointer';?>;opacity:<?php echo $fainted?'0.4':'1';?>;" <?php if($fainted) echo 'disabled'; ?>>
                    <span style="font-size:28px;"><?php echo $se; ?></span>
                    <div style="flex:1;text-align:left;">
                      <div style="font-family:'Press Start 2P',monospace;font-size:9px;color:var(--text);margin-bottom:5px;"><?php echo htmlspecialchars($sm['name']); ?> <span style="color:var(--muted);">Lv.<?php echo $sm['level']??1;?></span></div>
                      <div style="font-size:10px;color:var(--muted);margin-bottom:4px;"><?php echo $sm['type'];?> — <?php echo $sm['hp'];?>/<?php echo $sm['max_hp'];?> HP</div>
                      <div style="background:rgba(255,255,255,0.07);border-radius:999px;height:5px;overflow:hidden;"><div style="height:100%;border-radius:999px;width:<?php echo $shp;?>%;background:<?php echo $sc;?>;"></div></div>
                    </div>
                    <?php echo $fainted ? '<span style="color:var(--red);font-size:12px;font-weight:700;">FAINTED</span>' : '<span style="color:var(--green);font-size:18px;">→</span>'; ?>
                  </button>
                </form>
              <?php endforeach; ?>
            </div>
            <button class="cancel-btn" onclick="document.getElementById('trainerSwitchOverlay').classList.remove('open')">Cancel</button>
          </div>
        </div>
        <style>#trainerSwitchOverlay.open { display: flex !important; }</style>
        <?php endif; ?>

      <?php endif; ?>


    <?php endif; // end trainer battle ?>

    <?php
    // --- ZONE 2: ROCKY PEAKS ---
    if (($_SESSION['zone'] ?? '') == 2):
      $_SESSION['last_map_zone'] = 2;
      $z2TrainerUnlocked = (bool)($_SESSION['trainer_unlocked'] ?? false);
      $z2WildCreatures = array_values(array_filter($creaturesData['creatures'], function($c) { return $c['category'] === 'wild_zone2'; }));
    ?>
      <style>
        #zone2-wrap { display:flex; flex-direction:column; align-items:center; gap:12px; }
        #zone2Canvas {
          border: 3px solid rgba(148,163,184,0.25);
          border-radius: 12px;
          image-rendering: pixelated;
          box-shadow: 0 0 40px rgba(100,180,255,0.15);
          display: block;
        }
        #zone2-hud {
          width: 480px;
          display: flex;
          justify-content: space-between;
          align-items: center;
          font-size: 12px;
          color: var(--muted);
        }
        #zone2-hud span strong { color: #93c5fd; }
        #z2-controls-hint { font-size:11px; color:var(--muted); text-align:center; opacity:0.7; }
      </style>

      <div id="zone2-wrap">
        <div id="zone2-hud">
          <span>🏔️ <strong>Zone 2: Rocky Peaks</strong></span>
          <span>Use <strong>Arrow Keys</strong> or <strong>WASD</strong> to move</span>
        </div>
        <canvas id="zone2Canvas" width="480" height="480"></canvas>
        <div id="z2-controls-hint">
          Walk into cracked rock 🪨 to encounter wild creatures!
          <?php if ($z2TrainerUnlocked): ?>
            <span style="color:#f59e0b;font-weight:800;">⚔️ Find Trainer Mira!</span>
          <?php endif; ?>
        </div>
      </div>

      <div id="battle-flash"></div>

      <form id="z2-trainer-form" action="game.php" method="POST" style="display:none;">
        <input type="hidden" name="trainer_encounter" value="true">
      </form>

      <div id="encounter-popup">
        <div id="encounter-box" style="border-color:#93c5fd;box-shadow:0 0 40px rgba(100,180,255,0.3);">
          <span class="mob-sprite" id="mob-emoji">❓</span>
          <h3 id="encounter-name" style="color:#93c5fd;">Wild Creature!</h3>
          <p id="encounter-type" style="font-size:11px;color:#93c5fd;font-weight:700;letter-spacing:1px;text-transform:uppercase;margin-bottom:6px;"></p>
          <p id="encounter-text">A wild creature appeared!</p>
          <div id="encounter-stats" style="display:flex;gap:14px;justify-content:center;margin-bottom:20px;font-size:12px;font-weight:700;color:var(--muted);"></div>
          <form action="game.php" method="POST">
            <input type="hidden" name="wild_encounter" value="true">
            <button class="btn btn-danger" type="submit">⚔️ Battle!</button>
          </form>
        </div>
      </div>

      <script>
      (function() {
        const TILE = 32, COLS = 15, ROWS = 15;
        const canvas = document.getElementById('zone2Canvas');
        const ctx = canvas.getContext('2d');

        // Tile types: 0=stone path, 1=snow, 2=cracked rock (encounter), 3=boulder(wall),
        //             4=ice lake, 5=lava crack, 6=crystal, 7=trainer
        const T = { PATH:0, SNOW:1, CRACK:2, BOULDER:3, ICE:4, LAVA:5, CRYSTAL:6, TRAINER:7 };

        const TRAINER_X = 7, TRAINER_Y = 1;
        const trainerUnlocked = <?php echo json_encode($z2TrainerUnlocked); ?>;

        // Rocky Peaks map — cracked rocks everywhere, ice lake in centre, lava vents, crystals
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

        let px = 7, py = 7, facing = 'down', stepFrame = 0, moving = false;
        let lastMoveTime = 0;
        const MOVE_DELAY = 150;
        const keys = {};
        let inEncounter = false;

        const TYPE_EMOJI = {
          'Bug':'🐛','Flying':'🦅','Fire':'🔥','Water':'💧','Grass':'🌿',
          'Electric':'⚡','Normal':'🐾','Rock':'🪨','Ghost':'👻','Psychic':'🔮',
          'Ice':'🧊','Dragon':'🐉','Dark':'🌑','Steel':'⚙️'
        };

        const WILD_CREATURES = <?php echo json_encode($z2WildCreatures); ?>;

        function isWalkable(x, y) {
          if (x < 0 || y < 0 || x >= COLS || y >= ROWS) return false;
          const t = MAP[y][x];
          return t !== T.BOULDER && t !== T.ICE;
        }
        function isCrack(x, y) { return MAP[y][x] === T.CRACK; }

        // ── TILE DRAWING ──────────────────────────────────────────────────────
        function drawTile(tx, ty) {
          const t = MAP[ty][tx];
          const x = tx * TILE, y = ty * TILE;

          // Base fill
          const baseColors = {
            [T.PATH]:    '#5a5a6e',
            [T.SNOW]:    '#c8d8e8',
            [T.CRACK]:   '#6b5a4e',
            [T.BOULDER]: '#3a3a4a',
            [T.ICE]:     '#a0d4f0',
            [T.LAVA]:    '#3a2a1a',
            [T.CRYSTAL]: '#c8d8e8',
            [T.TRAINER]: '#5a5a6e',
          };
          ctx.fillStyle = baseColors[t] ?? '#555';
          ctx.fillRect(x, y, TILE, TILE);

          if (t === T.PATH) {
            // Stone path — subtle grid lines
            ctx.strokeStyle = 'rgba(0,0,0,0.18)';
            ctx.lineWidth = 1;
            ctx.strokeRect(x+2, y+2, TILE-4, TILE-4);
          }

          if (t === T.SNOW) {
            // Snow sparkles
            ctx.fillStyle = 'rgba(255,255,255,0.5)';
            [[x+6,y+6],[x+18,y+14],[x+26,y+8],[x+10,y+22]].forEach(([sx,sy]) => {
              ctx.fillRect(sx, sy, 2, 2);
            });
          }

          if (t === T.CRACK) {
            // Dark cracked rock with yellow crack lines
            ctx.strokeStyle = '#b8860b';
            ctx.lineWidth = 1.5;
            ctx.beginPath();
            ctx.moveTo(x+8,  y+6);  ctx.lineTo(x+16, y+14);
            ctx.moveTo(x+16, y+14); ctx.lineTo(x+12, y+24);
            ctx.moveTo(x+16, y+14); ctx.lineTo(x+24, y+20);
            ctx.stroke();
          }

          if (t === T.BOULDER) {
            ctx.fillStyle = '#2a2a36';
            ctx.fillRect(x+2, y+8, TILE-4, TILE-10);
            ctx.fillStyle = '#4a4a5e';
            ctx.beginPath();
            ctx.arc(x+16, y+10, 9, 0, Math.PI);
            ctx.fill();
          }

          if (t === T.ICE) {
            // Icy shimmer
            const tw = Date.now() / 1200;
            ctx.fillStyle = `rgba(180,230,255,${0.3 + Math.sin(tw + tx + ty)*0.2})`;
            ctx.fillRect(x+2, y+2, TILE-4, TILE-4);
            ctx.strokeStyle = 'rgba(255,255,255,0.4)';
            ctx.lineWidth = 1;
            ctx.beginPath();
            ctx.moveTo(x+4, y+10); ctx.lineTo(x+12, y+6);
            ctx.stroke();
          }

          if (t === T.LAVA) {
            // Lava glow pulsing
            const tl = Date.now() / 500;
            const glow = 0.4 + Math.sin(tl + tx*0.7)*0.3;
            ctx.fillStyle = `rgba(255,80,0,${glow})`;
            ctx.fillRect(x+6, y+6, TILE-12, TILE-12);
            ctx.fillStyle = `rgba(255,180,0,${glow*0.6})`;
            ctx.fillRect(x+10, y+10, TILE-20, TILE-20);
          }

          if (t === T.CRYSTAL) {
            // Teal crystal shards
            ctx.fillStyle = '#4dd9d9';
            ctx.beginPath();
            ctx.moveTo(x+16, y+4); ctx.lineTo(x+22, y+14);
            ctx.lineTo(x+16, y+28); ctx.lineTo(x+10, y+14);
            ctx.closePath();
            ctx.fill();
            ctx.fillStyle = 'rgba(255,255,255,0.4)';
            ctx.beginPath();
            ctx.moveTo(x+16, y+4); ctx.lineTo(x+19, y+14); ctx.lineTo(x+16, y+10);
            ctx.closePath();
            ctx.fill();
          }
        }

        // ── TRAINER NPC ───────────────────────────────────────────────────────
        function drawTrainer() {
          const tx = TRAINER_X * TILE, ty = TRAINER_Y * TILE;
          const pulse = Math.sin(Date.now() / 400) * 0.5 + 0.5;

          if (trainerUnlocked) {
            ctx.save();
            ctx.shadowColor = '#93c5fd';
            ctx.shadowBlur = 14 + pulse * 10;
            ctx.fillStyle = 'rgba(147,197,253,0.2)';
            ctx.beginPath();
            ctx.arc(tx+16, ty+16, 18, 0, Math.PI*2);
            ctx.fill();
            ctx.restore();
          }

          // Body — ice-blue coat
          ctx.fillStyle = trainerUnlocked ? '#1d6fa4' : '#4b5563';
          ctx.fillRect(tx+9, ty+16, 14, 12);
          // Head
          ctx.fillStyle = '#fcd5b0';
          ctx.beginPath();
          ctx.arc(tx+16, ty+12, 8, 0, Math.PI*2);
          ctx.fill();
          // White hair (Mira)
          ctx.fillStyle = '#e0e8f0';
          ctx.fillRect(tx+9, ty+5, 14, 7);
          // Eyes
          ctx.fillStyle = '#1e293b';
          ctx.fillRect(tx+13, ty+11, 2, 2);
          ctx.fillRect(tx+17, ty+11, 2, 2);
          // Legs
          ctx.fillStyle = '#1e3a5f';
          ctx.fillRect(tx+10, ty+28, 5, 5);
          ctx.fillRect(tx+17, ty+28, 5, 5);

          ctx.font = 'bold 9px sans-serif';
          ctx.textAlign = 'center';
          if (trainerUnlocked) {
            ctx.fillStyle = '#93c5fd';
            ctx.fillText('⚔ MIRA', tx+16, ty-4);
          } else {
            ctx.fillStyle = 'rgba(255,255,255,0.35)';
            ctx.fillText('MIRA', tx+16, ty-4);
          }
          ctx.textAlign = 'left';
        }

        // ── PLAYER SPRITE (same system, slightly darker palette) ─────────────
        function drawPlayer() {
          const x = px * TILE, y = py * TILE;
          const bob = moving ? (stepFrame === 1 ? -1 : 0) : 0;

          ctx.fillStyle = 'rgba(0,0,0,0.25)';
          ctx.beginPath();
          ctx.ellipse(x+16, y+33, 8, 3, 0, 0, Math.PI*2);
          ctx.fill();

          ctx.fillStyle = '#0f172a';
          ctx.fillRect(x+9, y+16+bob, 14, 12);
          ctx.fillStyle = '#fcd5b0';
          ctx.beginPath();
          ctx.arc(x+16, y+12+bob, 8, 0, Math.PI*2);
          ctx.fill();

          ctx.fillStyle = '#92400e';
          if (facing === 'down')       { ctx.fillRect(x+9, y+6+bob, 14, 6); ctx.fillRect(x+13, y+11+bob, 2, 2); ctx.fillRect(x+17, y+11+bob, 2, 2); }
          else if (facing === 'up')    { ctx.fillRect(x+9, y+6+bob, 14, 6); }
          else if (facing === 'left')  { ctx.fillRect(x+11, y+11+bob, 2, 2); }
          else if (facing === 'right') { ctx.fillRect(x+19, y+11+bob, 2, 2); }

          ctx.fillStyle = '#1d4ed8';
          ctx.fillRect(x+8, y+7+bob, 16, 4);
          ctx.fillRect(x+10, y+4+bob, 12, 4);

          ctx.fillStyle = '#1e3a5f';
          const legOff = moving && stepFrame === 1 ? 2 : 0;
          ctx.fillRect(x+10, y+28+bob, 5, 6+legOff);
          ctx.fillRect(x+17, y+28+bob, 5, 6-legOff);
        }

        function draw() {
          ctx.clearRect(0, 0, canvas.width, canvas.height);
          for (let ry = 0; ry < ROWS; ry++)
            for (let rx = 0; rx < COLS; rx++)
              drawTile(rx, ry);
          drawTrainer();
          drawPlayer();
        }

        document.addEventListener('keydown', e => { keys[e.key] = true; });
        document.addEventListener('keyup',   e => { keys[e.key] = false; });

        function tryMove() {
          if (inEncounter) return;
          const now = Date.now();
          if (now - lastMoveTime < MOVE_DELAY) return;

          let nx = px, ny = py, moved = false;
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
          const flash = document.getElementById('battle-flash');
          let flashes = 0;
          const iv = setInterval(() => {
            flash.style.opacity = flashes % 2 === 0 ? '0.85' : '0';
            if (++flashes >= 6) { clearInterval(iv); flash.style.opacity = '0'; showEncounterPopup(); }
          }, 120);
        }

        function showEncounterPopup() {
          if (!WILD_CREATURES.length) { document.getElementById('encounter-form') && document.getElementById('encounter-form').submit(); return; }
          const c = WILD_CREATURES[Math.floor(Math.random() * WILD_CREATURES.length)];
          const emoji = TYPE_EMOJI[c.type] || '❓';
          document.getElementById('mob-emoji').textContent = emoji;
          document.getElementById('encounter-name').textContent = `Wild ${c.name}!`;
          document.getElementById('encounter-type').textContent = c.type + ' Type';
          document.getElementById('encounter-text').textContent = `A wild ${c.name} emerged from the rocks!`;
          document.getElementById('encounter-stats').innerHTML =
            `<span>❤️ ${c.hp}</span><span>⚔️ ${c.attack}</span><span>🛡️ ${c.defense}</span><span>💨 ${c.speed}</span>`;
          document.getElementById('encounter-popup').classList.add('show');
        }

        function gameLoop() { tryMove(); draw(); requestAnimationFrame(gameLoop); }
        window.addEventListener('keydown', e => {
          if (['ArrowUp','ArrowDown','ArrowLeft','ArrowRight'].includes(e.key)) e.preventDefault();
        });
        gameLoop();
      })();
      </script>

    <?php endif; // end zone 2 ?>

  </div>

</body>
</html>