<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>PokéQuest</title>
<style>
*{margin:0;padding:0;box-sizing:border-box;}
html,body{width:100%;height:100%;overflow:hidden;background:#000;font-family:'Courier New',monospace;color:#fff;user-select:none;}

/* ── FULLSCREEN GAME WRAPPER ── */
#gameWrapper{
  position:fixed;inset:0;
  display:flex;align-items:center;justify-content:center;
  background:#000;
}
/* The canvas scales via JS transform-origin center */
#gameCanvas{
  display:block;
  image-rendering:pixelated;
  image-rendering:crisp-edges;
  transform-origin:top left;
  position:absolute;
  top:0;left:0;
}
/* Inner overlay container that scales WITH the canvas */
#gameScale{
  position:absolute;
  top:0;left:0;
  width:480px;height:432px;
  transform-origin:top left;
  pointer-events:none;
}
#gameScale>*{pointer-events:auto;}

/* HUD — pinned to bottom of screen, full width */
#hud{
  position:fixed;bottom:0;left:0;width:100%;
  background:rgba(22,33,62,.97);border-top:3px solid #FFD700;
  padding:5px 20px;display:flex;justify-content:space-around;
  font-size:13px;z-index:100;
}
#hud span{color:#FFD700;}

/* PARTY PANEL — floating right side */
#partyPanel{
  position:fixed;right:0;top:0;bottom:40px;
  width:170px;
  background:rgba(22,33,62,.96);
  border-left:3px solid #FFD700;
  padding:10px;overflow-y:auto;z-index:100;
}
#partyPanel h2{color:#FFD700;font-size:.85rem;margin-bottom:7px;text-align:center;letter-spacing:2px;border-bottom:2px solid #FFD70044;padding-bottom:5px;}
.party-slot{background:#0f3460;border:2px solid #334;border-radius:7px;padding:6px;margin-bottom:5px;cursor:pointer;transition:border-color .2s,background .2s;position:relative;}
.party-slot:hover{border-color:#FFD700;}
.party-slot.active-lead{border-color:#4ade80;background:#0a2a1a;}
.party-slot.empty{opacity:.3;cursor:default;border-style:dashed;display:flex;align-items:center;justify-content:center;font-size:10px;color:#555;min-height:40px;}
.party-slot.empty:hover{border-color:#334;}
.slot-header{display:flex;align-items:center;gap:4px;}
.slot-emoji{font-size:1.2rem;}
.slot-info{flex:1;min-width:0;}
.slot-name{font-size:10px;font-weight:bold;color:#FFD700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.slot-level{font-size:9px;color:#aaa;}
.slot-hp-wrap{background:#333;border-radius:3px;height:4px;margin-top:3px;overflow:hidden;}
.slot-hp-bar{height:100%;border-radius:3px;transition:width .3s;}
.slot-hp-text{font-size:8px;color:#aaa;margin-top:1px;}
.lead-badge{position:absolute;top:2px;right:2px;background:#4ade80;color:#000;font-size:7px;padding:1px 3px;border-radius:2px;font-weight:bold;}
#partyInfo{font-size:9px;color:#666;text-align:center;margin-top:4px;}

/* AREA BANNER */
#areaBanner{position:absolute;top:0;left:0;width:100%;text-align:center;padding:10px 0;font-size:1rem;font-weight:bold;letter-spacing:3px;pointer-events:none;z-index:50;transition:opacity .5s;}
#areaBanner.hidden{opacity:0;}

/* DIALOGUE BOX */
#dialogueBox{display:none;position:absolute;bottom:0;left:0;width:100%;z-index:40;background:rgba(10,20,40,.97);border-top:3px solid #FFD700;padding:14px 18px 12px;}
#dialogueBox.active{display:block;}
#dialogueSpeaker{font-size:11px;color:#FFD700;font-weight:bold;letter-spacing:2px;margin-bottom:5px;}
#dialogueText{font-size:13px;color:#eee;line-height:1.6;min-height:36px;}
#dialoguePrompt{font-size:10px;color:#aaa;text-align:right;margin-top:6px;animation:blink 1s infinite;}
@keyframes blink{0%,100%{opacity:1;}50%{opacity:0;}}

.exclaim-wrap{position:absolute;pointer-events:none;z-index:35;}
.exclaim{background:#FFD700;color:#000;font-size:11px;font-weight:bold;padding:2px 5px;border-radius:4px;white-space:nowrap;box-shadow:0 2px 6px #0006;}

/* BATTLE SCREEN */
#battleScreen{display:none;position:absolute;inset:0;flex-direction:column;align-items:center;justify-content:center;z-index:10;padding:12px;}
#battleScreen.active{display:flex;}
#battleBg{position:absolute;inset:0;z-index:0;}
#battleContent{position:relative;z-index:1;width:100%;display:flex;flex-direction:column;align-items:center;}
.battle-title{font-size:1.05rem;color:#FFD700;margin-bottom:6px;letter-spacing:2px;text-align:center;}
#trainerStrip{display:none;width:100%;background:rgba(20,10,60,.85);border:2px solid #7c3aed;border-radius:7px;padding:4px 12px;margin-bottom:5px;text-align:center;font-size:11px;color:#c4b5fd;}
#trainerStrip.active{display:block;}
#trainerMonList{font-size:10px;color:#aaa;margin-top:2px;}
.battle-area{display:flex;justify-content:space-around;width:100%;margin-bottom:6px;gap:8px;}
.pokemon-card{background:rgba(22,33,62,.93);border:2px solid #FFD700;border-radius:8px;padding:7px 10px;text-align:center;flex:1;}
.pokemon-sprite{font-size:2.2rem;margin-bottom:2px;}
.pokemon-name{color:#FFD700;font-weight:bold;font-size:12px;}
.pokemon-lvl{color:#aaa;font-size:10px;}
.hp-bar-wrap{background:#333;border-radius:4px;height:8px;margin-top:3px;overflow:hidden;}
.hp-bar{height:100%;border-radius:4px;transition:width .4s;}
.hp-text{font-size:10px;color:#aaa;margin-top:2px;}
.exp-bar-wrap{background:#1a1a3e;border-radius:3px;height:4px;margin-top:4px;overflow:hidden;border:1px solid #334;}
.exp-bar{height:100%;border-radius:3px;background:linear-gradient(90deg,#60a5fa,#a78bfa);transition:width 0.6s ease;box-shadow:0 0 4px #60a5fa88;}
.exp-label{font-size:9px;color:#60a5fa;margin-top:2px;letter-spacing:1px;}
.battle-log{background:rgba(10,22,40,.93);border:2px solid #334;border-radius:6px;padding:6px 10px;width:100%;min-height:36px;font-size:12px;margin-bottom:5px;text-align:center;color:#ddd;line-height:1.5;}
#battleTabs{display:flex;gap:4px;margin-bottom:5px;}
.tab-btn{background:#1a1a3e;border:2px solid #334;border-radius:6px;padding:4px 8px;font-family:inherit;font-size:10px;color:#aaa;cursor:pointer;transition:all .15s;}
.tab-btn.active,.tab-btn:hover{border-color:#FFD700;color:#FFD700;}
.battle-panel{display:none;width:100%;}
.battle-panel.active{display:flex;flex-wrap:wrap;gap:5px;justify-content:center;}
.battle-btn{background:#1d3557;color:#fff;border:2px solid #334;border-radius:6px;padding:7px 12px;font-family:inherit;font-size:11px;cursor:pointer;font-weight:bold;transition:all .15s;min-width:95px;}
.battle-btn:hover:not(:disabled){border-color:#FFD700;background:#2a4a7f;}
.battle-btn:disabled{opacity:.3;cursor:default;}
.battle-btn.flee-btn{background:#2a1a1a;border-color:#7f1d1d;}
.battle-btn.flee-btn:hover:not(:disabled){background:#3f1f1f;border-color:#ef4444;}
.battle-btn.catch-btn{background:#1a2a1a;border-color:#166534;}
.battle-btn.catch-btn:hover:not(:disabled){background:#1e3a1e;border-color:#4ade80;}
.switch-slot{background:#0f3460;border:2px solid #334;border-radius:6px;padding:5px 8px;cursor:pointer;display:flex;align-items:center;gap:7px;width:100%;transition:border-color .15s;margin-bottom:3px;font-size:11px;}
.switch-slot:hover{border-color:#4ade80;}
.switch-slot.fainted,.switch-slot.is-lead{opacity:.4;cursor:default;}
.switch-slot.is-lead:hover,.switch-slot.fainted:hover{border-color:#334;}

#catchAnim{display:none;position:absolute;inset:0;background:rgba(0,0,0,.85);z-index:20;flex-direction:column;align-items:center;justify-content:center;font-size:3rem;}
#catchAnim.active{display:flex;}
#catchAnim p{font-size:12px;color:#aaa;margin-top:10px;}

#transitionOverlay{display:none;position:absolute;inset:0;background:#000;z-index:60;align-items:center;justify-content:center;flex-direction:column;gap:12px;}
#transitionOverlay.active{display:flex;}
#transitionText{font-size:1.2rem;color:#FFD700;letter-spacing:4px;text-align:center;}
#transitionSub{font-size:.8rem;color:#aaa;letter-spacing:2px;}

/* NOTIF */
#notif{position:fixed;top:14px;left:50%;transform:translateX(-50%);background:#1a2a1a;border:2px solid #4ade80;border-radius:8px;padding:8px 18px;font-size:12px;color:#4ade80;z-index:300;display:none;box-shadow:0 4px 18px #4ade8044;max-width:360px;text-align:center;}

/* ── STARTER SCREEN ── */
#starterScreen{
  position:fixed;inset:0;background:#0d0d1a;z-index:999;
  display:flex;flex-direction:column;align-items:center;justify-content:center;
  gap:20px;
}
#starterScreen h2{font-size:1.4rem;color:#FFD700;letter-spacing:4px;text-shadow:2px 2px 0 #e63946;}
#starterScreen p{font-size:12px;color:#aaa;letter-spacing:1px;text-align:center;}
#starterCards{display:flex;gap:18px;flex-wrap:wrap;justify-content:center;}
.starter-card{
  background:#16213e;border:3px solid #334;border-radius:14px;
  padding:20px 18px;text-align:center;cursor:pointer;
  transition:border-color .2s,transform .2s,box-shadow .2s;
  width:140px;position:relative;
}
.starter-card:hover{transform:translateY(-6px);}
.starter-card.grass{border-color:#166534;}
.starter-card.grass:hover{border-color:#4ade80;box-shadow:0 0 22px #4ade8066;}
.starter-card.fire{border-color:#7f1d1d;}
.starter-card.fire:hover{border-color:#ef4444;box-shadow:0 0 22px #ef444466;}
.starter-card.water{border-color:#1e3a5f;}
.starter-card.water:hover{border-color:#38bdf8;box-shadow:0 0 22px #38bdf866;}
.starter-card.selected{transform:translateY(-8px) scale(1.04);}
.starter-card.grass.selected{border-color:#4ade80;box-shadow:0 0 28px #4ade8088;}
.starter-card.fire.selected{border-color:#ef4444;box-shadow:0 0 28px #ef444488;}
.starter-card.water.selected{border-color:#38bdf8;box-shadow:0 0 28px #38bdf888;}
.starter-sprite{font-size:3.2rem;margin-bottom:10px;display:block;}
.starter-name{font-size:13px;font-weight:bold;margin-bottom:4px;}
.starter-card.grass .starter-name{color:#4ade80;}
.starter-card.fire .starter-name{color:#ef4444;}
.starter-card.water .starter-name{color:#38bdf8;}
.starter-type{font-size:10px;letter-spacing:2px;margin-bottom:8px;}
.starter-card.grass .starter-type{color:#86efac;}
.starter-card.fire .starter-type{color:#fca5a5;}
.starter-card.water .starter-type{color:#7dd3fc;}
.starter-desc{font-size:10px;color:#888;line-height:1.5;}
.starter-stats{font-size:10px;color:#aaa;margin-top:8px;line-height:1.6;text-align:left;background:#0f1e3a;border-radius:6px;padding:6px 8px;}
.starter-choose-btn{
  background:#FFD700;color:#000;border:none;border-radius:8px;
  padding:11px 36px;font-family:inherit;font-size:13px;font-weight:bold;
  cursor:pointer;letter-spacing:2px;transition:transform .15s,box-shadow .2s;
  display:none;
}
.starter-choose-btn.visible{display:block;}
.starter-choose-btn:hover{transform:scale(1.05);box-shadow:0 0 20px #FFD70088;}
#starterConfirm{font-size:12px;color:#666;min-height:16px;text-align:center;}

@keyframes starterFadeOut{
  0%{opacity:1;transform:scale(1);}
  100%{opacity:0;transform:scale(1.08);}
}
#starterScreen.fade-out{animation:starterFadeOut .5s forwards;}
</style>
</head>
<body>

<!-- ══ STARTER SELECTION SCREEN ══ -->
<div id="starterScreen">
  <h2>⚡ PokéQuest</h2>
  <p>Welcome to the world of Pokémon!<br>Professor Oak has a gift for you — choose your first partner!</p>
  <div id="starterCards">

    <div class="starter-card grass" onclick="selectStarter('leafling')">
      <span class="starter-sprite">🌿</span>
      <div class="starter-name">LEAFLING</div>
      <div class="starter-type">🌿 GRASS TYPE</div>
      <div class="starter-desc">A gentle sprout with<br>a nurturing spirit.</div>
      <div class="starter-stats">
        ❤️ HP: High<br>
        ⚔️ ATK: Medium<br>
        🛡️ DEF: High<br>
        ✨ Moves: Vine Whip,<br>&nbsp;&nbsp;Razor Leaf, Tackle, Growl
      </div>
    </div>

    <div class="starter-card fire" onclick="selectStarter('embrite')">
      <span class="starter-sprite">🦎</span>
      <div class="starter-name">EMBRITE</div>
      <div class="starter-type">🔥 FIRE TYPE</div>
      <div class="starter-desc">A blazing lizard with<br>a fierce fighting heart.</div>
      <div class="starter-stats">
        ❤️ HP: Medium<br>
        ⚔️ ATK: Very High<br>
        🛡️ DEF: Low<br>
        ✨ Moves: Ember, Flare,<br>&nbsp;&nbsp;Scratch, Growl
      </div>
    </div>

    <div class="starter-card water" onclick="selectStarter('torrtle')">
      <span class="starter-sprite">🐢</span>
      <div class="starter-name">TORRTLE</div>
      <div class="starter-type">💧 WATER TYPE</div>
      <div class="starter-desc">A sturdy turtle with<br>a calm, defensive nature.</div>
      <div class="starter-stats">
        ❤️ HP: Medium<br>
        ⚔️ ATK: Medium<br>
        🛡️ DEF: Very High<br>
        ✨ Moves: Water Gun, Bubble,<br>&nbsp;&nbsp;Tackle, Harden
      </div>
    </div>

  </div>
  <div id="starterConfirm"></div>
  <button class="starter-choose-btn" id="starterChooseBtn" onclick="confirmStarter()">CHOOSE THIS PARTNER!</button>
</div>

<h1 style="display:none">⚡ PokéQuest</h1>
<div id="gameWrapper">
  <canvas id="gameCanvas" width="480" height="432"></canvas>

  <!-- Scaled overlay: all in-game overlays live here and scale with canvas -->
  <div id="gameScale">
    <div id="areaBanner" class="hidden"></div>

    <!-- Dialogue -->
    <div id="dialogueBox">
      <div id="dialogueSpeaker">???</div>
      <div id="dialogueText">...</div>
      <div id="dialoguePrompt">▼ SPACE / ENTER</div>
    </div>

    <!-- Battle -->
    <div id="battleScreen">
      <canvas id="battleBg" width="480" height="432" style="position:absolute;inset:0;z-index:0;"></canvas>
      <div id="battleContent">
        <div class="battle-title" id="battleTitle">⚔️ WILD BATTLE!</div>
        <div id="trainerStrip">
          <span id="trainerStripName">TRAINER</span>
          <div id="trainerMonList"></div>
        </div>
        <div class="battle-area">
          <div class="pokemon-card">
            <div class="pokemon-sprite" id="enemySprite">🐛</div>
            <div class="pokemon-name" id="enemyName">CATERPIL</div>
            <div class="pokemon-lvl" id="enemyLvl">Lv.1</div>
            <div class="hp-bar-wrap"><div class="hp-bar" id="enemyHpBar" style="width:100%;background:#4ade80;"></div></div>
            <div class="hp-text" id="enemyHpText">HP: 20/20</div>
          </div>
          <div class="pokemon-card">
            <div class="pokemon-sprite" id="playerSprite">🐉</div>
            <div class="pokemon-name" id="playerPokeName">DRAKELING</div>
            <div class="pokemon-lvl" id="playerLvl">Lv.1</div>
            <div class="hp-bar-wrap"><div class="hp-bar" id="playerHpBar" style="width:100%;background:#4ade80;"></div></div>
            <div class="hp-text" id="playerHpText">HP: 30/30</div>
            <div class="exp-bar-wrap"><div class="exp-bar" id="playerExpBar" style="width:0%;"></div></div>
            <div class="exp-label" id="playerExpText">EXP 0 / 100</div>
          </div>
        </div>
        <div class="battle-log" id="battleLog">A wild Pokémon appeared!</div>
        <div id="battleTabs">
          <button class="tab-btn active" onclick="showTab('fight')">⚔️ Fight</button>
          <button class="tab-btn" id="tab-catch-btn" onclick="showTab('catch')">🎒 Bag</button>
          <button class="tab-btn" onclick="showTab('switch')">🔄 Switch</button>
          <button class="tab-btn" id="tab-run-btn" onclick="showTab('run')">🏃 Run</button>
        </div>
        <div class="battle-panel active" id="panel-fight">
          <button class="battle-btn" id="btn-m0" onclick="playerAttack(0)">—</button>
          <button class="battle-btn" id="btn-m1" onclick="playerAttack(1)">—</button>
          <button class="battle-btn" id="btn-m2" onclick="playerAttack(2)">—</button>
          <button class="battle-btn" id="btn-m3" onclick="playerAttack(3)">—</button>
        </div>
        <div class="battle-panel" id="panel-catch">
          <button class="battle-btn catch-btn" id="btn-pokeball" onclick="throwBall('pokeball')">🔵 Poké Ball (<span id="ballCount">5</span>)</button>
          <button class="battle-btn catch-btn" id="btn-greatball" onclick="throwBall('greatball')">🟣 Great Ball (<span id="greatCount">0</span>)</button>
        </div>
        <div class="battle-panel" id="panel-switch"></div>
        <div class="battle-panel" id="panel-run">
          <button class="battle-btn flee-btn" onclick="fleeBattle()">🏃 Run Away</button>
        </div>
      </div>
      <div id="catchAnim">
        <div id="catchBall">🔵</div>
        <p id="catchMsg">Throwing...</p>
      </div>
    </div>

    <!-- Transition -->
    <div id="transitionOverlay">
      <div id="transitionText">ROUTE 2</div>
      <div id="transitionSub">ENTERING NEW AREA...</div>
    </div>
  </div><!-- /gameScale -->
</div><!-- /gameWrapper -->

<!-- HUD fixed at bottom -->
<div id="hud">
  <div>🐾 <span id="hudLead">—</span></div>
  <div>❤️ <span id="hpDisplay">—</span></div>
  <div>⭐ Lv<span id="lvlDisplay">—</span></div>
  <div>👟 <span id="stepsDisplay">0</span></div>
  <div><span id="areaTag">🌿 PALLET PLAINS</span></div>
  <div style="font-size:10px;color:#555;">WASD/Arrows · Space=talk</div>
</div>

<!-- Party panel fixed right -->
<div id="partyPanel">
  <h2>🎒 PARTY</h2>
  <div id="partySlots"></div>
  <div id="partyInfo"></div>
</div>
<div id="notif"></div>

<script>
// ════════════════════════════════════════════════
//  CONSTANTS
// ════════════════════════════════════════════════
const T={GRASS:0,TALL:1,WATER:2,PATH:3,TREE:4,SAND:5,WARP:6,SIGN:7,ROCK:8,CAVE:9,FLOWER:10,SNOW:11};
const TILE=32,CANVAS_W=480,CANVAS_H=432,COLS=15,ROWS=13;

// ════════════════════════════════════════════════
//  TRAINER DEFINITIONS
// ════════════════════════════════════════════════
const TRAINER_DEFS={
  lass:{
    id:'lass',
    name:'LASS LILY',
    sprite:'',
    color:'#f9a8d4',
    area:'pallet',
    tx:9, ty:4,           // tile position on the map
    facing:'left',        // direction they look
    sightDir:'left',      // direction they can see the player
    sightRange:4,
    greeting:'Well hello there, traveller!',
    challenge:'I challenge you to a Pokémon battle!',
    defeat:'Oh no! You\'re so strong! Take these balls as a prize.',
    win:'Better luck next time, challenger!',
    reward:{pokeball:2, greatball:0},
    team:[ makePending('caterpil',6), makePending('sparrow',7) ],
  },
  rockguy:{
    id:'rockguy',
    name:'HIKER ROK',
    sprite:'🧗',
    color:'#a78bfa',
    area:'cave',
    tx:10, ty:4,
    facing:'left',
    sightDir:'left',
    sightRange:5,
    greeting:'These caves are MY territory, stranger.',
    challenge:'Prepare yourself! My Pokémon are forged in stone!',
    defeat:'Incredible... You\'ve conquered the cave! Here, take this.',
    win:'The caves always win! Hahaha!',
    reward:{pokeball:1, greatball:1},
    team:[ makePending('pebbler',9), makePending('venomite',8), makePending('zappchu',10) ],
  },
};

// placeholder so makePending can be called before makeMon is defined
function makePending(id,lvl){return{__pending:true,id,lvl};}
// resolve pending after makeMon defined
function resolvePending(t){if(t.__pending){return makeMon(t.id,t.lvl);}return t;}

// Trainer runtime state (defeated flag, current team index in battle)
const trainerState={};
Object.keys(TRAINER_DEFS).forEach(k=>{trainerState[k]={defeated:false};});

// ════════════════════════════════════════════════
//  AREAS
// ════════════════════════════════════════════════
const AREAS={
  pallet:{
    name:'PALLET PLAINS', tag:'🌿',
    skyTop:'#87ceeb', skyBot:'#c8f0a8',
    grassColor:'#4ade80', tallColor:'#166534', pathColor:'#d4a373',
    wildPool:['caterpil','sparrow','aquafin','buzzwing'],
    trainers:['lass'],
    map:[
      [4,4,4,4,4,4,4,4,4,4,4,4,4,4,4],
      [4,10,0,0,0,1,1,0,0,0,1,1,0,10,4],
      [4,0,0,3,3,3,3,3,0,0,0,0,0,0,4],
      [4,0,0,3,7,0,0,3,0,1,1,1,0,0,4],
      [4,1,0,3,0,0,0,3,0,1,1,1,0,0,4],
      [4,1,0,3,0,2,2,3,0,0,0,0,0,0,4],
      [4,0,0,3,0,2,2,3,3,3,3,3,0,0,4],
      [4,0,0,3,0,0,0,0,0,0,3,0,0,0,4],
      [4,0,1,3,0,0,3,3,3,0,3,0,1,1,4],
      [4,0,1,3,0,0,3,5,3,0,3,0,1,1,4],
      [4,0,0,3,0,0,3,5,3,0,3,0,0,0,4],
      [4,0,0,3,3,3,3,3,3,3,3,6,0,0,4],
      [4,4,4,4,4,4,4,4,4,4,4,4,4,4,4],
    ],
    playerStart:{x:7,y:6},
    warpTarget:{area:'cave',x:2,y:6},
  },
  cave:{
    name:'CRYSTAL CAVE', tag:'🏔️',
    skyTop:'#1a0a2e', skyBot:'#2d1a4e',
    grassColor:'#5b21b6', tallColor:'#312e81', pathColor:'#7c6a4a',
    wildPool:['zappchu','frostail','venomite','flameling'],
    trainers:['rockguy'],
    map:[
      [4,4,4,4,4,4,4,4,4,4,4,4,4,4,4],
      [4,9,9,9,9,9,0,0,0,9,9,9,9,9,4],
      [4,9,0,0,0,9,0,1,1,9,0,0,0,9,4],
      [4,9,0,1,0,9,0,1,1,9,0,1,0,9,4],
      [4,9,0,1,0,0,0,0,0,0,0,1,0,9,4],
      [4,9,0,0,0,3,3,3,3,3,0,0,0,9,4],
      [4,6,0,0,0,3,8,8,8,3,0,0,0,9,4],
      [4,9,0,0,0,3,8,8,8,3,0,0,0,9,4],
      [4,9,0,1,0,3,3,3,3,3,0,1,0,9,4],
      [4,9,0,1,0,0,0,0,0,0,0,1,0,9,4],
      [4,9,0,0,0,9,1,1,1,9,0,0,0,9,4],
      [4,9,9,9,9,9,0,0,0,9,9,9,9,9,4],
      [4,4,4,4,4,4,4,4,4,4,4,4,4,4,4],
    ],
    playerStart:{x:2,y:6},
    warpTarget:{area:'pallet',x:11,y:10},
  },
};

let currentArea='pallet';
function getArea(){return AREAS[currentArea];}
function getMap(){return getArea().map;}
function getTrainersForArea(){
  return getArea().trainers.map(id=>TRAINER_DEFS[id]);
}

// ════════════════════════════════════════════════
//  TILE COLOR
// ════════════════════════════════════════════════
function tileColor(type){
  const a=getArea();
  switch(type){
    case T.GRASS:  return a.grassColor||'#4ade80';
    case T.TALL:   return a.tallColor||'#166534';
    case T.WATER:  return '#38bdf8';
    case T.PATH:   return a.pathColor||'#d4a373';
    case T.TREE:   return currentArea==='cave'?'#1e1b4b':'#15803d';
    case T.SAND:   return '#fde68a';
    case T.WARP:   return '#facc15';
    case T.SIGN:   return '#92400e';
    case T.ROCK:   return '#78716c';
    case T.CAVE:   return '#1c1917';
    case T.FLOWER: return '#4ade80';
    case T.SNOW:   return '#e0f2fe';
    default:       return '#222';
  }
}

// ════════════════════════════════════════════════
//  POKÉMON DATA
// ════════════════════════════════════════════════
const PDEFS={
  // ── STARTERS ──
  leafling: {name:'LEAFLING', sprite:'🌿',baseHp:34,atk:7, def:6, moves:['Vine Whip','Razor Leaf','Tackle','Growl']},
  embrite:  {name:'EMBRITE',  sprite:'🦎',baseHp:28,atk:11,def:2, moves:['Ember','Flare','Scratch','Growl']},
  torrtle:  {name:'TORRTLE',  sprite:'🐢',baseHp:30,atk:6, def:9, moves:['Water Gun','Bubble','Tackle','Harden']},
  // ── WILD ──
  drakeling:{name:'DRAKELING',sprite:'🐉',baseHp:30,atk:8,def:3,moves:['Ember','Dragon Claw','Tackle','Growl']},
  caterpil: {name:'CATERPIL', sprite:'🐛',baseHp:20,atk:4,def:1,moves:['Tackle','String Shot','Harden','Scratch']},
  sparrow:  {name:'SPARROW',  sprite:'🐦',baseHp:18,atk:6,def:2,moves:['Peck','Gust','Quick Attack','Growl']},
  pebbler:  {name:'PEBBLER',  sprite:'🪨',baseHp:28,atk:5,def:6,moves:['Rock Throw','Harden','Tackle','Rollout']},
  flameling:{name:'FLAMELING',sprite:'🔥',baseHp:22,atk:9,def:1,moves:['Flare','Ember','Quick Attack','Scratch']},
  aquafin:  {name:'AQUAFIN',  sprite:'🐟',baseHp:24,atk:6,def:3,moves:['Water Gun','Tackle','Bite','Growl']},
  buzzwing: {name:'BUZZWING', sprite:'🦋',baseHp:19,atk:7,def:2,moves:['Wing Attack','Gust','Powder','Tackle']},
  zappchu:  {name:'ZAPPCHU',  sprite:'⚡',baseHp:21,atk:8,def:2,moves:['Thunderbolt','Quick Attack','Thunder Wave','Scratch']},
  frostail: {name:'FROSTAIL', sprite:'🦊',baseHp:23,atk:7,def:3,moves:['Ice Shard','Bite','Tail Whip','Scratch']},
  venomite: {name:'VENOMITE', sprite:'🐍',baseHp:26,atk:6,def:4,moves:['Poison Fang','Wrap','Bite','Harden']},
};
const MDEFS={
  'Vine Whip':    {power:10,acc:.95,emoji:'🌿'},
  'Razor Leaf':   {power:12,acc:.9, emoji:'🍃'},
  'Bubble':       {power:8, acc:1.0,emoji:'🫧'},
  'Ember':        {power:10,acc:.9, emoji:'🔥'},
  'Dragon Claw':  {power:12,acc:.85,emoji:'🐉'},
  'Tackle':       {power:7, acc:1.0,emoji:'💥'},
  'Growl':        {power:0, acc:1.0,emoji:'😤',effect:'debuff'},
  'Scratch':      {power:6, acc:1.0,emoji:'🖐️'},
  'String Shot':  {power:0, acc:1.0,emoji:'🕸️',effect:'debuff'},
  'Harden':       {power:0, acc:1.0,emoji:'🛡️',effect:'buff'},
  'Peck':         {power:8, acc:.95,emoji:'🐦'},
  'Gust':         {power:7, acc:1.0,emoji:'💨'},
  'Quick Attack': {power:6, acc:1.0,emoji:'💨'},
  'Rock Throw':   {power:10,acc:.9, emoji:'🪨'},
  'Rollout':      {power:9, acc:.9, emoji:'🌀'},
  'Flare':        {power:14,acc:.8, emoji:'🔥'},
  'Water Gun':    {power:10,acc:.95,emoji:'💧'},
  'Bite':         {power:9, acc:.95,emoji:'🦷'},
  'Wing Attack':  {power:9, acc:.95,emoji:'🦋'},
  'Powder':       {power:5, acc:1.0,emoji:'✨'},
  'Thunderbolt':  {power:12,acc:.85,emoji:'⚡'},
  'Thunder Wave': {power:0, acc:.9, emoji:'⚡',effect:'debuff'},
  'Ice Shard':    {power:10,acc:.95,emoji:'❄️'},
  'Tail Whip':    {power:0, acc:1.0,emoji:'🦊',effect:'debuff'},
  'Poison Fang':  {power:11,acc:.9, emoji:'🐍'},
  'Wrap':         {power:6, acc:1.0,emoji:'🌀'},
};

function makeMon(defId,level=1){
  const d=PDEFS[defId];
  const maxHp=d.baseHp+level*4;
  const moves=d.moves.map(name=>({name,...(MDEFS[name]||{power:5,acc:1.0,emoji:'⚡'})}));
  return{id:defId,name:d.name,sprite:d.sprite,level,maxHp,hp:maxHp,
         atk:d.atk+level,def:d.def+Math.floor(level/2),moves,exp:0,expToNext:level*20};
}

// ════════════════════════════════════════════════
//  GAME STATE
// ════════════════════════════════════════════════
const MAX_PARTY=6;
let party=[];   // filled after starter selection
let leadIndex=0;
const inventory={pokeball:5,greatball:0};
const player={x:7,y:6,px:7*TILE,py:6*TILE,moving:false,dir:'down',steps:0,wins:0};
function getLead(){return party[leadIndex]||null;}

// Battle state — extended for trainer battles
const battle={
  active:false, enemy:null, busy:false,
  isTrainer:false,
  trainer:null,          // TRAINER_DEFS entry
  trainerTeam:[],        // resolved mon array
  trainerMonIdx:0,       // which of the trainer's mons is currently out
};

// ════════════════════════════════════════════════
//  DIALOGUE STATE
// ════════════════════════════════════════════════
let dialogue={
  active:false,
  lines:[],
  lineIdx:0,
  onDone:null,
};

function startDialogue(speaker, lines, onDone){
  dialogue={active:true,lines,lineIdx:0,onDone:onDone||null};
  document.getElementById('dialogueSpeaker').textContent=speaker;
  document.getElementById('dialogueText').textContent=lines[0];
  document.getElementById('dialogueBox').classList.add('active');
}
function advanceDialogue(){
  if(!dialogue.active)return;
  dialogue.lineIdx++;
  if(dialogue.lineIdx>=dialogue.lines.length){
    // done
    document.getElementById('dialogueBox').classList.remove('active');
    dialogue.active=false;
    if(dialogue.onDone)dialogue.onDone();
  } else {
    document.getElementById('dialogueText').textContent=dialogue.lines[dialogue.lineIdx];
  }
}

// ════════════════════════════════════════════════
//  INPUT
// ════════════════════════════════════════════════
const MOVE_KEYS={ArrowUp:'up',ArrowDown:'down',ArrowLeft:'left',ArrowRight:'right',
                 w:'up',s:'down',a:'left',d:'right'};
const keys={};
document.addEventListener('keydown',e=>{
  keys[e.key]=true;
  // dialogue advance
  if((e.key===' '||e.key==='Enter')&&dialogue.active){
    e.preventDefault();
    advanceDialogue();
  }
});
document.addEventListener('keyup',e=>{delete keys[e.key];});

// ════════════════════════════════════════════════
//  MAP HELPERS
// ════════════════════════════════════════════════
function getTile(tx,ty){
  const m=getMap();
  if(ty<0||ty>=ROWS||tx<0||tx>=COLS)return T.TREE;
  return m[ty][tx];
}
function isWalkable(tx,ty){
  const t=getTile(tx,ty);
  if(t===T.TREE||t===T.WATER||t===T.ROCK||t===T.CAVE||t===T.SIGN)return false;
  // block trainer's tile
  for(const tr of getTrainersForArea()){
    if(!trainerState[tr.id].defeated&&tr.tx===tx&&tr.ty===ty)return false;
  }
  return true;
}
function isWarp(tx,ty){return getTile(tx,ty)===T.WARP;}

// ════════════════════════════════════════════════
//  TRAINER LINE-OF-SIGHT
// ════════════════════════════════════════════════
function checkTrainerSight(){
  for(const tr of getTrainersForArea()){
    if(trainerState[tr.id].defeated)continue;
    const dx=player.x-tr.tx, dy=player.y-tr.ty;
    let inSight=false;
    if(tr.sightDir==='left'  &&dy===0&&dx<0&&dx>=-tr.sightRange)inSight=true;
    if(tr.sightDir==='right' &&dy===0&&dx>0&&dx<= tr.sightRange)inSight=true;
    if(tr.sightDir==='up'    &&dx===0&&dy<0&&dy>=-tr.sightRange)inSight=true;
    if(tr.sightDir==='down'  &&dx===0&&dy>0&&dy<= tr.sightRange)inSight=true;
    if(inSight){
      triggerTrainer(tr);
      return;
    }
  }
}

let exclamShowing=null;
let exclamTimer=null;

function triggerTrainer(tr){
  if(battle.active||dialogue.active||transitioning)return;
  // show ! above trainer
  showExclaim(tr);
  setTimeout(()=>{
    hideExclaim();
    startDialogue(tr.name,[tr.greeting,tr.challenge],()=>{
      beginTrainerBattle(tr);
    });
  },900);
}

function showExclaim(tr){
  hideExclaim();
  const wrap=document.createElement('div');
  wrap.className='exclaim-wrap';
  wrap.id='exclaim-wrap';
  const ex=document.createElement('div');
  ex.className='exclaim';
  ex.textContent='!';
  wrap.appendChild(ex);
  // position above trainer on canvas
  const sx=(tr.tx*TILE-camX)+(TILE/2)-12;
  const sy=(tr.ty*TILE-camY)-22;
  wrap.style.left=sx+'px';
  wrap.style.top=sy+'px';
  document.getElementById('gameWrapper').appendChild(wrap);
  exclamShowing=wrap;
}
function hideExclaim(){
  if(exclamShowing){exclamShowing.remove();exclamShowing=null;}
}

// ════════════════════════════════════════════════
//  TRAINER BATTLE
// ════════════════════════════════════════════════
function beginTrainerBattle(tr){
  // Resolve pending mons
  const team=tr.team.map(resolvePending);
  tr.team=team; // replace with resolved copies (fresh HP each time for same battle)
  // actually make fresh copies so HP is full
  const freshTeam=team.map(m=>makeMon(m.id,m.level));

  battle.isTrainer=true;
  battle.trainer=tr;
  battle.trainerTeam=freshTeam;
  battle.trainerMonIdx=0;
  battle.enemy=freshTeam[0];
  battle.active=true;
  battle.busy=false;

  // update title
  document.getElementById('battleTitle').textContent='⚔️ TRAINER BATTLE!';
  // trainer strip
  const strip=document.getElementById('trainerStrip');
  strip.classList.add('active');
  document.getElementById('trainerStripName').textContent=tr.sprite+' '+tr.name;
  updateTrainerMonList();

  // disable bag (can't catch in trainer battles) & run tab
  document.getElementById('tab-catch-btn').disabled=true;
  document.getElementById('tab-catch-btn').style.opacity='0.35';
  document.getElementById('tab-run-btn').disabled=true;
  document.getElementById('tab-run-btn').style.opacity='0.35';

  updateBattleUI();
  setBattleLog(tr.name+' sent out '+battle.enemy.name+'!');
  drawBattleBg(getArea());
  document.getElementById('battleScreen').classList.add('active');
  showTab('fight');
}

function updateTrainerMonList(){
  const dots=battle.trainerTeam.map((m,i)=>{
    const alive=m.hp>0;
    const isCurrent=i===battle.trainerMonIdx;
    return `<span style="color:${isCurrent?'#FFD700':alive?'#4ade80':'#555'}">${m.sprite}</span>`;
  }).join(' ');
  document.getElementById('trainerMonList').innerHTML=dots;
}

// Trainer sends next mon
function trainerSendNextMon(){
  const tr=battle.trainer;
  battle.trainerMonIdx++;
  if(battle.trainerMonIdx>=battle.trainerTeam.length){
    // trainer defeated
    endTrainerBattle(true);
    return;
  }
  battle.enemy=battle.trainerTeam[battle.trainerMonIdx];
  updateTrainerMonList();
  updateBattleUI();
  setBattleLog(tr.name+' sent out '+battle.enemy.name+'!');
  setBusy(false);
}

function endTrainerBattle(won){
  const tr=battle.trainer;
  battle.active=false;
  battle.isTrainer=false;
  document.getElementById('battleScreen').classList.remove('active');
  document.getElementById('trainerStrip').classList.remove('active');
  document.getElementById('tab-catch-btn').disabled=false;
  document.getElementById('tab-catch-btn').style.opacity='1';
  document.getElementById('tab-run-btn').disabled=false;
  document.getElementById('tab-run-btn').style.opacity='1';
  document.getElementById('battleTitle').textContent='⚔️ WILD BATTLE!';

  if(won){
    trainerState[tr.id].defeated=true;
    // give reward
    inventory.pokeball+=tr.reward.pokeball||0;
    inventory.greatball+=tr.reward.greatball||0;
    player.wins++;
    renderPartyPanel();updateHUD();
    setTimeout(()=>{
      startDialogue(tr.name,[tr.defeat,'You received: '+(tr.reward.pokeball?'🔵×'+tr.reward.pokeball+' ':'')+(tr.reward.greatball?'🟣×'+tr.reward.greatball:'')+'!'],null);
      showNotif(tr.name+' defeated! 🏆');
    },400);
  } else {
    party.forEach(p=>{if(p.hp<=0)p.hp=Math.floor(p.maxHp*.35)+1;});
    renderPartyPanel();updateHUD();
    setTimeout(()=>startDialogue(tr.name,[tr.win],null),400);
  }
}

// ════════════════════════════════════════════════
//  WILD BATTLE START
// ════════════════════════════════════════════════
function startBattle(){
  const a=getArea(),lead=getLead();
  const lvl=Math.max(1,lead.level+Math.floor(Math.random()*4)-2);
  const pool=a.wildPool;
  const defId=pool[Math.floor(Math.random()*pool.length)];
  battle.isTrainer=false;
  battle.enemy=makeMon(defId,lvl);
  battle.active=true;battle.busy=false;
  document.getElementById('battleTitle').textContent='⚔️ WILD BATTLE!';
  document.getElementById('trainerStrip').classList.remove('active');
  document.getElementById('tab-catch-btn').disabled=false;
  document.getElementById('tab-catch-btn').style.opacity='1';
  document.getElementById('tab-run-btn').disabled=false;
  document.getElementById('tab-run-btn').style.opacity='1';
  updateBattleUI();
  setBattleLog('A wild '+battle.enemy.name+' (Lv.'+lvl+') appeared!');
  drawBattleBg(a);
  document.getElementById('battleScreen').classList.add('active');
  showTab('fight');
}

function drawBattleBg(a){
  const bc=document.getElementById('battleBg');
  const bx=bc.getContext('2d');  const g=bx.createLinearGradient(0,0,0,432);
  g.addColorStop(0,a.skyTop||'#0a1628');
  g.addColorStop(1,a.skyBot||'#1a2a4a');
  bx.fillStyle=g;bx.fillRect(0,0,480,432);
  if(currentArea==='pallet'){
    bx.fillStyle='rgba(74,222,128,.12)';
    for(let i=0;i<8;i++){bx.beginPath();bx.arc(40+i*55,360+Math.sin(i)*20,30+i*4,0,Math.PI*2);bx.fill();}
  } else {
    bx.fillStyle='rgba(99,102,241,.15)';
    for(let i=0;i<6;i++){bx.beginPath();bx.arc(50+i*70,380,25,0,Math.PI*2);bx.fill();}
    bx.fillStyle='rgba(30,27,75,.8)';
    for(let i=0;i<12;i++){bx.beginPath();bx.moveTo(i*42,0);bx.lineTo(i*42+20,0);bx.lineTo(i*42+10,40+Math.sin(i)*20,0);bx.fill();}
  }
}

// ════════════════════════════════════════════════
//  BATTLE UI
// ════════════════════════════════════════════════
function showTab(tab){
  document.querySelectorAll('.tab-btn').forEach((b,i)=>{
    b.classList.toggle('active',['fight','catch','switch','run'][i]===tab);
  });
  document.querySelectorAll('.battle-panel').forEach(p=>p.classList.remove('active'));
  const panel=document.getElementById('panel-'+tab);
  if(panel)panel.classList.add('active');
  if(tab==='switch')renderSwitchPanel();
}

function updateBattleUI(){
  const lead=getLead(),en=battle.enemy;
  if(!en)return;
  document.getElementById('playerSprite').textContent=lead.sprite;
  document.getElementById('playerPokeName').textContent=lead.name;
  document.getElementById('playerLvl').textContent='Lv.'+lead.level;
  setHpBar('player',lead.hp,lead.maxHp);
  document.getElementById('enemySprite').textContent=en.sprite;
  document.getElementById('enemyName').textContent=en.name;
  document.getElementById('enemyLvl').textContent='Lv.'+en.level;
  setHpBar('enemy',en.hp,en.maxHp);
  lead.moves.forEach((mv,i)=>{
    const b=document.getElementById('btn-m'+i);
    if(!b)return;
    b.textContent=(mv.emoji||'⚡')+' '+mv.name;
    b.disabled=false;
  });
  document.getElementById('ballCount').textContent=inventory.pokeball;
  document.getElementById('greatCount').textContent=inventory.greatball;
  document.getElementById('btn-pokeball').disabled=inventory.pokeball<=0||battle.isTrainer;
  document.getElementById('btn-greatball').disabled=inventory.greatball<=0||battle.isTrainer;
  setExpBar();
}

function setHpBar(who,hp,max){
  const pct=Math.max(0,hp/max)*100;
  const bar=document.getElementById(who+'HpBar');
  bar.style.width=pct+'%';
  bar.style.background=pct>50?'#4ade80':pct>25?'#facc15':'#ef4444';
  document.getElementById(who+'HpText').textContent='HP: '+Math.max(0,hp)+'/'+max;
}
function setExpBar(){
  const lead=getLead();
  const pct=Math.min(100,(lead.exp/lead.expToNext)*100);
  document.getElementById('playerExpBar').style.width=pct+'%';
  document.getElementById('playerExpText').textContent='EXP '+lead.exp+' / '+lead.expToNext;
}
function setBattleLog(msg){document.getElementById('battleLog').textContent=msg;}
function setBusy(v){
  battle.busy=v;
  ['btn-m0','btn-m1','btn-m2','btn-m3','btn-pokeball','btn-greatball'].forEach(id=>{
    const b=document.getElementById(id);if(b)b.disabled=v||(id.includes('ball')&&battle.isTrainer);
  });
}

// ════════════════════════════════════════════════
//  BATTLE – FIGHT
// ════════════════════════════════════════════════
function playerAttack(idx){
  if(battle.busy)return;
  const lead=getLead(),mv=lead.moves[idx];
  if(!mv)return;
  setBusy(true);
  if(Math.random()>mv.acc){
    setBattleLog(lead.name+' used '+mv.name+'... but missed!');
    setTimeout(enemyTurn,1100);return;
  }
  let dmg=0;
  if(mv.power>0){
    dmg=Math.max(1,Math.floor(mv.power+lead.atk-battle.enemy.def+Math.random()*3));
    battle.enemy.hp=Math.max(0,battle.enemy.hp-dmg);
  }
  if(mv.effect==='buff') setBattleLog(lead.name+' raised its defense!');
  else if(mv.effect==='debuff'){setBattleLog(lead.name+' used '+mv.name+'! Enemy weakened!');battle.enemy.def=Math.max(1,battle.enemy.def-1);}
  else setBattleLog(lead.name+' used '+mv.name+'!'+(dmg?' Dealt '+dmg+' damage!':''));
  updateBattleUI();
  if(battle.enemy.hp<=0){
    setTimeout(handleEnemyFaint,1100);return;
  }
  setTimeout(enemyTurn,1100);
}

function handleEnemyFaint(){
  if(battle.isTrainer){
    const tr=battle.trainer;
    setBattleLog(battle.enemy.name+' fainted!');
    // exp for the lead
    const lead=getLead();
    const exp=battle.enemy.level*18;lead.exp+=exp;
    while(lead.exp>=lead.expToNext){
      lead.exp-=lead.expToNext;lead.level++;lead.expToNext=lead.level*20;
      lead.maxHp+=4;lead.hp=Math.min(lead.hp+6,lead.maxHp);lead.atk+=2;lead.def+=1;
    }
    updateBattleUI();setExpBar();renderPartyPanel();updateHUD();
    // check if trainer has more
    const remaining=battle.trainerTeam.slice(battle.trainerMonIdx+1).filter(m=>m.hp>0);
    if(remaining.length===0){
      setTimeout(()=>endTrainerBattle(true),1200);
    } else {
      setTimeout(()=>{
        setBattleLog(tr.name+' is about to send another Pokémon!');
        setTimeout(trainerSendNextMon,1200);
      },900);
    }
  } else {
    setTimeout(()=>endBattle(true),1100);
  }
}

function enemyTurn(){
  const en=battle.enemy,lead=getLead();
  const mv=en.moves[Math.floor(Math.random()*en.moves.length)];
  if(!mv){setBusy(false);return;}
  if(Math.random()>mv.acc){
    setBattleLog(en.name+' used '+mv.name+'... missed!');
    setBusy(false);return;
  }
  let dmg=0;
  if(mv.power>0){
    dmg=Math.max(1,Math.floor(mv.power+en.atk-lead.def+Math.random()*3));
    lead.hp=Math.max(0,lead.hp-dmg);
  }
  if(mv.effect==='debuff')setBattleLog(en.name+' used '+mv.name+'!');
  else setBattleLog(en.name+' used '+mv.name+'!'+(dmg?' You took '+dmg+' damage!':''));
  setHpBar('player',lead.hp,lead.maxHp);
  updateHUD();
  if(lead.hp<=0){
    const alive=party.filter(p=>p.hp>0);
    if(alive.length===0){
      setTimeout(()=>(battle.isTrainer?endTrainerBattle(false):endBattle(false)),1100);
    } else {
      setBattleLog(lead.name+' fainted! Switch your Pokémon!');
      showTab('switch');setBusy(false);
    }
    return;
  }
  setBusy(false);
}

// ════════════════════════════════════════════════
//  BATTLE – CATCH (wild only)
// ════════════════════════════════════════════════
function throwBall(type){
  if(battle.busy||battle.isTrainer)return;
  if(type==='pokeball'&&inventory.pokeball<=0)return;
  if(type==='greatball'&&inventory.greatball<=0)return;
  setBusy(true);
  if(type==='pokeball')inventory.pokeball--;else inventory.greatball--;
  updateBattleUI();
  const en=battle.enemy,hpRatio=en.hp/en.maxHp;
  const base=type==='greatball'?0.55:0.35;
  const caught=Math.random()<base+(1-hpRatio)*0.4;
  const anim=document.getElementById('catchAnim');
  const ball=document.getElementById('catchBall');
  const msg=document.getElementById('catchMsg');
  ball.textContent=type==='greatball'?'🟣':'🔵';
  msg.textContent='Throwing at '+en.name+'...';
  anim.classList.add('active');
  let shakes=0;
  const iv=setInterval(()=>{
    ball.style.transform=ball.style.transform==='rotate(20deg)'?'rotate(-20deg)':'rotate(20deg)';
    if(++shakes>=5){
      clearInterval(iv);ball.style.transform='none';
      setTimeout(()=>{
        if(caught){ball.textContent='⭐';msg.textContent=en.name+' was caught!';}
        else{ball.textContent='💨';msg.textContent='Oh no! It broke free!';}
        setTimeout(()=>{
          anim.classList.remove('active');
          if(caught){
            if(party.length<MAX_PARTY){const nm={...en,moves:en.moves.map(m=>({...m}))};nm.hp=Math.max(1,nm.hp);party.push(nm);showNotif(en.name+' caught! 🎉');}
            else showNotif(en.name+' caught! (Party full)');
            renderPartyPanel();updateHUD();
            battle.active=false;
            setTimeout(()=>document.getElementById('battleScreen').classList.remove('active'),1200);
          } else {
            setBattleLog(en.name+' broke free!');
            setBusy(false);setTimeout(enemyTurn,600);
          }
        },900);
      },400);
    }
  },350);
}

// ════════════════════════════════════════════════
//  BATTLE – SWITCH
// ════════════════════════════════════════════════
function renderSwitchPanel(){
  const panel=document.getElementById('panel-switch');panel.innerHTML='';
  party.forEach((p,i)=>{
    const isLead=i===leadIndex,fainted=p.hp<=0;
    const div=document.createElement('div');
    div.className='switch-slot'+(isLead?' is-lead':fainted?' fainted':'');
    div.innerHTML=`<span style="font-size:1.3rem">${p.sprite}</span>
      <div><b style="color:#FFD700">${p.name}</b> ${isLead?'(Lead)':''} ${fainted?'(Fainted)':''}<br>
      <span style="color:#aaa;font-size:10px">Lv${p.level} · HP:${p.hp}/${p.maxHp}</span></div>`;
    if(!isLead&&!fainted){
      div.addEventListener('click',()=>{
        leadIndex=i;battle.busy=false;
        updateBattleUI();renderPartyPanel();updateHUD();
        setBattleLog('Go, '+p.name+'!');
        showTab('fight');
        setTimeout(enemyTurn,800);
      });
    }
    panel.appendChild(div);
  });
}

// ════════════════════════════════════════════════
//  BATTLE – FLEE (wild only)
// ════════════════════════════════════════════════
function fleeBattle(){
  if(battle.busy||battle.isTrainer)return;
  setBusy(true);
  if(Math.random()<0.6){
    setBattleLog('Got away safely!');
    setTimeout(()=>{battle.active=false;document.getElementById('battleScreen').classList.remove('active');},900);
  } else {
    setBattleLog("Couldn't escape!");
    setTimeout(()=>{setBusy(false);enemyTurn();},800);
  }
}

// ════════════════════════════════════════════════
//  BATTLE – END (wild)
// ════════════════════════════════════════════════
function endBattle(won){
  battle.active=false;
  const lead=getLead(),en=battle.enemy;
  let log='';
  if(won){
    player.wins++;
    const exp=en.level*15;lead.exp+=exp;
    log=lead.name+' won! +'+exp+' EXP!';
    while(lead.exp>=lead.expToNext){
      lead.exp-=lead.expToNext;lead.level++;lead.expToNext=lead.level*20;
      lead.maxHp+=4;lead.hp=Math.min(lead.hp+6,lead.maxHp);lead.atk+=2;lead.def+=1;
      log+=' '+lead.name+' grew to Lv.'+lead.level+'! 🎉';
    }
    if(player.wins%3===0){inventory.pokeball+=2;log+=' Got 2 Poké Balls!';}
    if(player.wins%5===0){inventory.greatball++;log+=' Got a Great Ball! 🟣';}
    showNotif(log);
  } else {
    party.forEach(p=>{if(p.hp<=0)p.hp=Math.floor(p.maxHp*.3)+1;});
    log='All Pokémon fainted! Healed.';showNotif('You blacked out! 💤');
  }
  setBattleLog(log);renderPartyPanel();updateHUD();updateBattleUI();setExpBar();
  setTimeout(()=>document.getElementById('battleScreen').classList.remove('active'),2200);
}

// ════════════════════════════════════════════════
//  PARTY PANEL
// ════════════════════════════════════════════════
function renderPartyPanel(){
  const c=document.getElementById('partySlots');c.innerHTML='';
  for(let i=0;i<MAX_PARTY;i++){
    const slot=document.createElement('div');
    if(party[i]){
      const p=party[i],pct=p.maxHp>0?(p.hp/p.maxHp)*100:0,isLead=i===leadIndex;
      slot.className='party-slot'+(isLead?' active-lead':'');
      slot.innerHTML=(isLead?'<div class="lead-badge">LEAD</div>':'')+
        '<div class="slot-header">'+
        '<div class="slot-emoji">'+p.sprite+'</div>'+
        '<div class="slot-info">'+
        '<div class="slot-name">'+p.name+'</div>'+
        '<div class="slot-level">Lv'+p.level+(p.hp<=0?' 💀':'')+'</div>'+
        '<div class="slot-hp-wrap"><div class="slot-hp-bar" style="width:'+pct+'%;background:'+(pct>50?'#4ade80':pct>25?'#facc15':'#ef4444')+'"></div></div>'+
        '<div class="slot-hp-text">'+p.hp+'/'+p.maxHp+' HP</div>'+
        '<div class="slot-hp-wrap" style="background:#1a1a3e;margin-top:3px;border:1px solid #334;"><div class="slot-hp-bar" style="width:'+Math.min(100,p.exp/p.expToNext*100).toFixed(1)+'%;background:linear-gradient(90deg,#60a5fa,#a78bfa);box-shadow:0 0 4px #60a5fa66;"></div></div>'+
        '<div class="slot-hp-text" style="color:#60a5fa;">EXP '+p.exp+'/'+p.expToNext+'</div>'+
        '</div></div>';
      if(p.hp>0)slot.onclick=()=>{if(!battle.active){leadIndex=i;renderPartyPanel();updateHUD();}};
      else slot.style.opacity='0.5';
    } else {
      slot.className='party-slot empty';slot.textContent='Empty';
    }
    c.appendChild(slot);
  }
  document.getElementById('partyInfo').textContent=party.length+'/'+MAX_PARTY+' · 🔵×'+inventory.pokeball+' 🟣×'+inventory.greatball;
}

function updateHUD(){
  const lead=getLead();
  const a=getArea();
  document.getElementById('hudLead').textContent=lead?lead.name:'—';
  document.getElementById('hpDisplay').textContent=lead?lead.hp+'/'+lead.maxHp:'—';
  document.getElementById('lvlDisplay').textContent=lead?lead.level:'—';
  document.getElementById('stepsDisplay').textContent=player.steps;
  document.getElementById('areaTag').textContent=a.tag+' '+a.name;
}

// ════════════════════════════════════════════════
//  NOTIF
// ════════════════════════════════════════════════
let nTimer=null;
function showNotif(msg){
  const el=document.getElementById('notif');
  el.textContent=msg;el.style.display='block';
  if(nTimer)clearTimeout(nTimer);
  nTimer=setTimeout(()=>el.style.display='none',3200);
}

// ════════════════════════════════════════════════
//  AREA TRANSITION
// ════════════════════════════════════════════════
let transitioning=false;
function doWarp(){
  if(transitioning)return;
  transitioning=true;
  const wt=getArea().warpTarget;
  const destArea=AREAS[wt.area];
  const ov=document.getElementById('transitionOverlay');
  document.getElementById('transitionText').textContent=destArea.tag+' '+destArea.name;
  document.getElementById('transitionSub').textContent='ENTERING NEW AREA...';
  ov.classList.add('active');
  setTimeout(()=>{
    currentArea=wt.area;
    player.x=wt.x;player.y=wt.y;
    player.px=player.x*TILE;player.py=player.y*TILE;
    moveQueue=null;
    updateHUD();
    showAreaBanner(destArea.tag+' '+destArea.name,destArea.grassColor||'#4ade80');
    setTimeout(()=>{ov.classList.remove('active');transitioning=false;},600);
  },1200);
}

let bannerTimer=null;
function showAreaBanner(text,color){
  const b=document.getElementById('areaBanner');
  b.textContent=text;b.style.color=color;
  b.style.background='rgba(0,0,0,.7)';
  b.style.textShadow='0 0 10px '+color;
  b.classList.remove('hidden');
  if(bannerTimer)clearTimeout(bannerTimer);
  bannerTimer=setTimeout(()=>b.classList.add('hidden'),2500);
}

// ════════════════════════════════════════════════
//  CANVAS SCALING (fullscreen)
// ════════════════════════════════════════════════
const canvas=document.getElementById('gameCanvas');
const ctx=canvas.getContext('2d');
const gameScale=document.getElementById('gameScale');
const battleBgCanvas=document.getElementById('battleBg');

function resizeGame(){
  const hudH=40; // HUD height
  const partyW=170; // party panel width
  const availW=window.innerWidth-partyW;
  const availH=window.innerHeight-hudH;
  const scaleX=availW/CANVAS_W;
  const scaleY=availH/CANVAS_H;
  const scale=Math.min(scaleX,scaleY);
  const scaledW=CANVAS_W*scale;
  const scaledH=CANVAS_H*scale;
  const offX=Math.floor((availW-scaledW)/2);
  const offY=Math.floor((availH-scaledH)/2);

  canvas.style.transform=`scale(${scale})`;
  canvas.style.transformOrigin='top left';
  canvas.style.left=offX+'px';
  canvas.style.top=offY+'px';

  gameScale.style.transform=`scale(${scale})`;
  gameScale.style.transformOrigin='top left';
  gameScale.style.left=offX+'px';
  gameScale.style.top=offY+'px';
}
window.addEventListener('resize',resizeGame);
let camX=0,camY=0;
let moveQueue=null,moveProgress=0;

function drawBg(){
  const a=getArea();
  const g=ctx.createLinearGradient(0,0,0,CANVAS_H);
  g.addColorStop(0,a.skyTop||'#87ceeb');
  g.addColorStop(1,a.skyBot||'#c8f0a8');
  ctx.fillStyle=g;ctx.fillRect(0,0,CANVAS_W,CANVAS_H);
}

function drawTile(tx,ty){
  const type=getMap()[ty][tx];
  const sx=tx*TILE-camX,sy=ty*TILE-camY;
  ctx.fillStyle=tileColor(type);
  ctx.fillRect(sx,sy,TILE,TILE);
  if(type===T.FLOWER){
    ctx.fillStyle='#4ade80';ctx.fillRect(sx,sy,TILE,TILE);
    ctx.fillStyle='#fff';ctx.fillRect(sx+6,sy+8,4,4);ctx.fillRect(sx+18,sy+14,4,4);
    ctx.fillStyle='#fbbf24';ctx.fillRect(sx+8,sy+6,2,2);ctx.fillRect(sx+20,sy+12,2,2);
  }
  if(type===T.TALL){
    ctx.fillStyle=currentArea==='cave'?'#1e1b4b':'#14532d';
    ctx.fillRect(sx+3,sy+3,TILE-6,TILE-6);
    ctx.fillStyle=getArea().tallColor||'#166534';
    for(let i=0;i<3;i++)ctx.fillRect(sx+5+i*8,sy+5,3,15);
  }
  if(type===T.TREE){
    const ic=currentArea==='cave';
    ctx.fillStyle=ic?'#1e1b4b':'#14532d';
    ctx.beginPath();ctx.arc(sx+TILE/2,sy+TILE/2,13,0,Math.PI*2);ctx.fill();
    ctx.fillStyle=ic?'#312e81':'#166534';
    ctx.beginPath();ctx.arc(sx+TILE/2,sy+TILE/2-3,10,0,Math.PI*2);ctx.fill();
  }
  if(type===T.WATER){
    const wt=Date.now()/1200;
    ctx.fillStyle='#7dd3fc';ctx.fillRect(sx+2,sy+10+Math.sin(wt+tx)*2,TILE-4,6);
    ctx.fillStyle='rgba(255,255,255,.2)';ctx.fillRect(sx+6,sy+16+Math.cos(wt+ty)*2,TILE-12,3);
  }
  if(type===T.PATH){
    ctx.fillStyle=getArea().pathColor||'#b08040';ctx.fillRect(sx+1,sy+1,TILE-2,TILE-2);
    ctx.fillStyle='rgba(0,0,0,.1)';
    ctx.fillRect(sx+1,sy+1,TILE-2,3);ctx.fillRect(sx+1,sy+TILE-4,TILE-2,3);
  }
  if(type===T.SAND){
    ctx.fillStyle='#fde68a';ctx.fillRect(sx,sy,TILE,TILE);
    ctx.fillStyle='#fbbf24';
    for(let i=0;i<4;i++)ctx.fillRect(sx+5+(i%2)*14,sy+5+Math.floor(i/2)*14,4,4);
  }
  if(type===T.WARP){
    const pulse=(Math.sin(Date.now()/400)+1)/2;
    ctx.fillStyle=`rgba(250,204,21,${.4+pulse*.4})`;ctx.fillRect(sx,sy,TILE,TILE);
    ctx.strokeStyle='#FFD700';ctx.lineWidth=2;ctx.strokeRect(sx+2,sy+2,TILE-4,TILE-4);
    ctx.fillStyle='#FFD700';ctx.font='16px serif';ctx.textAlign='center';
    ctx.fillText('🚪',sx+TILE/2,sy+TILE/2+6);ctx.textAlign='left';
  }
  if(type===T.ROCK){
    ctx.fillStyle='#57534e';ctx.fillRect(sx+3,sy+8,TILE-6,TILE-12);
    ctx.fillStyle='#78716c';ctx.fillRect(sx+5,sy+6,TILE-10,8);
    ctx.fillStyle='#a8a29e';ctx.fillRect(sx+7,sy+8,5,4);
  }
  if(type===T.CAVE){
    ctx.fillStyle='#0c0a09';ctx.fillRect(sx,sy,TILE,TILE);
    ctx.fillStyle='rgba(99,102,241,.15)';
    for(let i=0;i<3;i++)ctx.fillRect(sx+3+i*10,sy+4,4,TILE-8);
  }
  if(type===T.SIGN){
    ctx.fillStyle='#92400e';ctx.fillRect(sx+10,sy+16,12,12);
    ctx.fillStyle='#b45309';ctx.fillRect(sx+6,sy+8,20,12);
    ctx.fillStyle='#fef3c7';ctx.fillRect(sx+8,sy+10,16,8);
  }
  ctx.strokeStyle='rgba(0,0,0,.06)';ctx.lineWidth=1;ctx.strokeRect(sx,sy,TILE,TILE);
}

function drawTrainers(){
  for(const tr of getTrainersForArea()){
    const sx=tr.tx*TILE-camX, sy=tr.ty*TILE-camY;
    const defeated=trainerState[tr.id].defeated;

    // Shadow
    ctx.fillStyle='rgba(0,0,0,.2)';
    ctx.beginPath();ctx.ellipse(sx+TILE/2,sy+TILE-3,8,4,0,0,Math.PI*2);ctx.fill();

    // Trainer body (colored rectangle person)
    if(defeated){
      ctx.globalAlpha=0.5;
    }
    // torso
    ctx.fillStyle=tr.color;
    ctx.fillRect(sx+9,sy+12,14,16);
    // head
    ctx.fillStyle='#ffddc1';
    ctx.fillRect(sx+10,sy+3,12,12);
    // eyes
    ctx.fillStyle='#333';
    if(tr.facing!=='up'){
      ctx.fillRect(sx+12,sy+6,2,2);
      ctx.fillRect(sx+18,sy+6,2,2);
    }
    // hair (little colored block)
    ctx.fillStyle=tr.color;
    ctx.fillRect(sx+10,sy+3,12,4);
    // legs
    ctx.fillStyle='#374151';
    ctx.fillRect(sx+10,sy+27,5,7);
    ctx.fillRect(sx+17,sy+27,5,7);
    // sprite emoji floating above
    ctx.font='14px serif';ctx.textAlign='center';
    ctx.fillText(tr.sprite,sx+TILE/2,sy+1);
    ctx.textAlign='left';

    // Defeated tick
    if(defeated){
      ctx.globalAlpha=1;
      ctx.fillStyle='#4ade80';ctx.font='bold 14px monospace';ctx.textAlign='center';
      ctx.fillText('✓',sx+TILE/2,sy-2);ctx.textAlign='left';
    }

    // Sight ray (subtle) — only if not defeated
    if(!defeated){
      const range=tr.sightRange;
      let rx=tr.tx,ry=tr.ty,rdx=0,rdy=0;
      if(tr.sightDir==='left') rdx=-1;
      if(tr.sightDir==='right')rdx=1;
      if(tr.sightDir==='up')   rdy=-1;
      if(tr.sightDir==='down') rdy=1;
      ctx.strokeStyle='rgba(255,100,100,.18)';ctx.lineWidth=2;
      ctx.setLineDash([4,4]);
      ctx.beginPath();
      ctx.moveTo(sx+TILE/2,sy+TILE/2);
      ctx.lineTo(sx+TILE/2+rdx*range*TILE,sy+TILE/2+rdy*range*TILE);
      ctx.stroke();
      ctx.setLineDash([]);
    }
  }
}

function drawPlayer(){
  const px=player.px-camX,py=player.py-camY;
  const t=Date.now()/200,bob=player.moving?Math.sin(t)*2:0;
  ctx.fillStyle='rgba(0,0,0,.18)';ctx.beginPath();ctx.ellipse(px+TILE/2,py+TILE-3,8,4,0,0,Math.PI*2);ctx.fill();
  ctx.fillStyle='#e63946';ctx.fillRect(px+8,py+10+bob,16,18);
  ctx.fillStyle='#ffddc1';ctx.fillRect(px+9,py+2+bob,14,13);
  if(player.dir!=='up'){ctx.fillStyle='#333';ctx.fillRect(px+11,py+5+bob,3,3);ctx.fillRect(px+18,py+5+bob,3,3);}
  ctx.fillStyle='#c1121f';ctx.fillRect(px+8,py+2+bob,16,5);ctx.fillRect(px+6,py+3+bob,4,4);
  const leg=player.moving?Math.sin(t)*4:0;
  ctx.fillStyle='#1d3557';ctx.fillRect(px+9,py+26+bob,6,8-leg);ctx.fillRect(px+17,py+26+bob,6,8+leg);
}

// ════════════════════════════════════════════════
//  MOVEMENT
// ════════════════════════════════════════════════
function tryMove(dir){
  let nx=player.x,ny=player.y;
  if(dir==='up')   ny--;
  if(dir==='down') ny++;
  if(dir==='left') nx--;
  if(dir==='right')nx++;
  player.dir=dir;
  if(isWalkable(nx,ny)){
    moveQueue={fromX:player.x*TILE,fromY:player.y*TILE,toX:nx*TILE,toY:ny*TILE};
    player.x=nx;player.y=ny;moveProgress=0;
  }
}

// ════════════════════════════════════════════════
//  GAME LOOP
// ════════════════════════════════════════════════
let lastTime=0;
function gameLoop(ts){
  const dt=Math.min((ts-lastTime)/1000,.1);lastTime=ts;

  if(!battle.active&&!transitioning&&!dialogue.active&&party.length>0){
    if(!moveQueue){
      for(const[k,d] of Object.entries(MOVE_KEYS)){if(keys[k]){tryMove(d);break;}}
    }
    if(moveQueue){
      player.moving=true;moveProgress+=dt*5.5;
      if(moveProgress>=1){
        player.px=moveQueue.toX;player.py=moveQueue.toY;
        player.steps++;updateHUD();
        const tile=getTile(player.x,player.y);
        const allFainted=party.every(p=>p.hp<=0);
        moveQueue=null;player.moving=false;
        if(isWarp(player.x,player.y)){
          doWarp();
        } else if(!allFainted&&tile===T.TALL&&Math.random()<0.2){
          setTimeout(startBattle,80);
        } else {
          // check trainer sight every step
          checkTrainerSight();
        }
      } else {
        player.px=moveQueue.fromX+(moveQueue.toX-moveQueue.fromX)*moveProgress;
        player.py=moveQueue.fromY+(moveQueue.toY-moveQueue.fromY)*moveProgress;
      }
    } else {player.moving=false;}
  }

  // Camera
  const tcx=player.px-CANVAS_W/2+TILE/2;
  const tcy=player.py-CANVAS_H/2+TILE/2;
  camX=Math.max(0,Math.min(COLS*TILE-CANVAS_W,tcx));
  camY=Math.max(0,Math.min(ROWS*TILE-CANVAS_H,tcy));

  // Update exclaim position dynamically
  if(exclamShowing){
    // find which trainer is shouting
    for(const tr of getTrainersForArea()){
      const sx=(tr.tx*TILE-camX)+(TILE/2)-12;
      const sy=(tr.ty*TILE-camY)-22;
      exclamShowing.style.left=sx+'px';
      exclamShowing.style.top=sy+'px';
    }
  }

  drawBg();
  for(let r=0;r<ROWS;r++)for(let c=0;c<COLS;c++)drawTile(c,r);
  drawTrainers();
  drawPlayer();
  requestAnimationFrame(gameLoop);
}

// ════════════════════════════════════════════════
//  STARTER SELECTION
// ════════════════════════════════════════════════
let selectedStarter=null;

const STARTER_INFO={
  leafling:{type:'grass', typeColor:'#4ade80', confirmMsg:'LEAFLING wants to be your partner!'},
  embrite: {type:'fire',  typeColor:'#ef4444', confirmMsg:'EMBRITE is ready to fight for you!'},
  torrtle: {type:'water', typeColor:'#38bdf8', confirmMsg:'TORRTLE will protect you on your journey!'},
};

function selectStarter(id){
  selectedStarter=id;
  // Highlight selected card
  document.querySelectorAll('.starter-card').forEach(c=>c.classList.remove('selected'));
  const info=STARTER_INFO[id];
  // find clicked card by its type class
  document.querySelector('.starter-card.'+info.type).classList.add('selected');
  // show confirm text and button
  document.getElementById('starterConfirm').textContent=info.confirmMsg;
  document.getElementById('starterConfirm').style.color=info.typeColor;
  const btn=document.getElementById('starterChooseBtn');
  btn.classList.add('visible');
  btn.style.background=info.typeColor;
  btn.style.color=(id==='embrite'||id==='torrtle')?'#fff':'#000';
}

function confirmStarter(){
  if(!selectedStarter)return;
  // Make the starter pokemon at level 5
  party=[makeMon(selectedStarter,5)];
  leadIndex=0;

  // Animate screen out
  const screen=document.getElementById('starterScreen');
  screen.classList.add('fade-out');
  setTimeout(()=>{
    screen.style.display='none';
    // Show professor dialogue then start game
    startDialogue('PROF. OAK',[
      'Excellent choice! Take good care of your new partner.',
      'The world is full of wild Pokémon to catch and trainers to battle.',
      'Walk through tall dark grass to find wild Pokémon, and use the 🚪 warp to reach new areas.',
      'Good luck, young trainer! Your adventure begins now!',
    ], ()=>{
      showAreaBanner(getArea().tag+' '+getArea().name,'#4ade80');
      showNotif('Adventure start! You received 5 Poké Balls! 🔵');
    });
    renderPartyPanel();
    updateHUD();
  },500);
}

// ════════════════════════════════════════════════
//  INIT
// ════════════════════════════════════════════════
resizeGame();
requestAnimationFrame(gameLoop);
</script>
</body>
</html>