<?php

define('GAMEPLAY_FILE', __DIR__ . '/data/gameplay.json');

// ─────────────────────────────────────────────
//  Read / Write helpers
// ─────────────────────────────────────────────

function readGameplayFile() {
    if (!file_exists(GAMEPLAY_FILE)) {
        return ['players' => []];
    }
    $json = file_get_contents(GAMEPLAY_FILE);
    $data = json_decode($json, true);
    return $data ?? ['players' => []];
}

function writeGameplayFile($data) {
    file_put_contents(
        GAMEPLAY_FILE,
        json_encode($data, JSON_PRETTY_PRINT)
    );
}

// ─────────────────────────────────────────────
//  1. findPlayer
//     Returns the player's saved data if the
//     username + password match, otherwise null.
// ─────────────────────────────────────────────

function findPlayer($username, $password) {
    $data = readGameplayFile();

    foreach ($data['players'] as $player) {
        if (
            strtolower($player['username']) === strtolower(trim($username)) &&
            password_verify($password, $player['password'])
        ) {
            return $player;
        }
    }

    return null;
}

// ─────────────────────────────────────────────
//  2. savePlayer
//     Updates an existing player's gameState
//     and lastSaved timestamp in gameplay.json.
// ─────────────────────────────────────────────

function savePlayer($username, $gameState) {
    $data = readGameplayFile();

    foreach ($data['players'] as &$player) {
        if (strtolower($player['username']) === strtolower(trim($username))) {
            $player['gameState']  = $gameState;
            $player['lastSaved']  = time();
            writeGameplayFile($data);
            return true;
        }
    }

    return false; // player not found
}

// ─────────────────────────────────────────────
//  3. createPlayer
//     Adds a brand-new player entry with a
//     fresh default gameState and saves to file.
// ─────────────────────────────────────────────

function createPlayer($username, $password, $difficulty) {
    $data = readGameplayFile();

    // Don't create a duplicate
    foreach ($data['players'] as $player) {
        if (strtolower($player['username']) === strtolower(trim($username))) {
            return false;
        }
    }

    $newPlayer = [
        'username'  => trim($username),
        'password'  => password_hash($password, PASSWORD_DEFAULT),
        'lastSaved' => time(),
        'gameState' => [
            'currentZone'  => 1,
            'difficulty'   => $difficulty,
            'starter'      => null,      // set after starter selection in game
            'team'         => [],
            'inventory'    => [
                'balls'       => 5,
                'rareBalls'   => 0,
                'potions'     => 2,
                'rarePotions' => 0,
            ],
            'zonesProgress' => [
                'zone1'  => ['cleared' => false, 'creaturesDefeated' => 0, 'trainerDefeated' => false],
                'zone2'  => ['cleared' => false, 'creaturesDefeated' => 0, 'trainerDefeated' => false],
                'zone3'  => ['cleared' => false, 'creaturesDefeated' => 0, 'trainerDefeated' => false],
                'zone4'  => ['cleared' => false, 'creaturesDefeated' => 0, 'trainerDefeated' => false],
                'zone5'  => ['cleared' => false, 'creaturesDefeated' => 0, 'trainerDefeated' => false],
                'zone6'  => ['cleared' => false, 'creaturesDefeated' => 0, 'trainerDefeated' => false],
                'zone7'  => ['cleared' => false, 'creaturesDefeated' => 0, 'trainerDefeated' => false],
                'zone8'  => ['cleared' => false, 'creaturesDefeated' => 0, 'trainerDefeated' => false],
                'zone9'  => ['cleared' => false, 'creaturesDefeated' => 0, 'trainerDefeated' => false],
                'zone10' => ['cleared' => false, 'creaturesDefeated' => 0, 'trainerDefeated' => false],
            ],
        ],
    ];

    $data['players'][] = $newPlayer;
    writeGameplayFile($data);
    return true;
}

// ─────────────────────────────────────────────
//  4. saveLeaderboard
//     Saves or updates a player's leaderboard
//     entry in leaderboard.json.
// ─────────────────────────────────────────────

define('LEADERBOARD_FILE', __DIR__ . '/data/leaderboard.json');

function saveLeaderboard($username, $score, $zonesCleared, $creaturesDefeated, $completionTime) {
    // Read existing leaderboard
    if (file_exists(LEADERBOARD_FILE)) {
        $json = file_get_contents(LEADERBOARD_FILE);
        $leaderboard = json_decode($json, true) ?? [];
    } else {
        $leaderboard = [];
    }

    // Check if player already has an entry — update if new score is higher
    $found = false;
    foreach ($leaderboard as &$entry) {
        if (strtolower($entry['username']) === strtolower(trim($username))) {
            $found = true;
            // Only update if this run has a better score
            if ($score > $entry['score']) {
                $entry['score']            = $score;
                $entry['zonesCleared']     = $zonesCleared;
                $entry['creaturesDefeated']= $creaturesDefeated;
                $entry['completionTime']   = $completionTime;
            }
            break;
        }
    }

    // New player — add fresh entry
    if (!$found) {
        $leaderboard[] = [
            'username'          => trim($username),
            'score'             => $score,
            'zonesCleared'      => $zonesCleared,
            'creaturesDefeated' => $creaturesDefeated,
            'completionTime'    => $completionTime,
        ];
    }

    file_put_contents(
        LEADERBOARD_FILE,
        json_encode($leaderboard, JSON_PRETTY_PRINT)
    );

    return true;
}