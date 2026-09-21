<?php
declare(strict_types=1);

/*
 * Tabellino live - partita di prova (Femi-CZ Rovigo v MPS Viadana, 23/05/2015)
 * tratta dal fac-simile FIR. Usata dal primo avvio (login.php) e dalla pagina
 * ripristina_demo.php.
 */

require __DIR__ . '/db.php';

function demo_db(): PDO
{
    return db_connect();
}

function demo_state(): array
{
    $json = <<<'JSON'
{"id":"DEMOID","info":{"luogo":"Rovigo – Stadio “Mario Battaglini”","data":"2015-05-23","campionato":"Eccellenza","giornata":"II giornata","note":"giornata afosa, campo in buone condizioni. Stadio Battaglini esaurito","spettatori":"4750","puntiH":"4","puntiA":"1","arbitro":"De Santis","aa1":"","aa2":"","quarto":"","tmo":"","potmLabel":"Player of the Match","potm":"Persico A. (Viadana)"},"minuteMode":"half","teams":{"h":{"name":"Femi-CZ Rovigo","coach":"Coppo","players":{"1":{"name":"Boccalon","cap":false},"2":{"name":"Mahoney","cap":false},"3":{"name":"Ravalle","cap":false},"4":{"name":"Reato","cap":true},"5":{"name":"Barion","cap":false},"6":{"name":"Anouer","cap":false},"7":{"name":"Burman","cap":false},"8":{"name":"Abadie","cap":false},"9":{"name":"Legora","cap":false},"10":{"name":"Bustos G.","cap":false},"11":{"name":"Pratichetti A.","cap":false},"12":{"name":"Pizarro","cap":false},"13":{"name":"Pedrazzi","cap":false},"14":{"name":"Calanchini","cap":false},"15":{"name":"Basson","cap":false},"16":{"name":"Damiano","cap":false},"17":{"name":"De Marchi An.","cap":false},"18":{"name":"Tumiati","cap":false},"19":{"name":"","cap":false},"20":{"name":"","cap":false},"21":{"name":"","cap":false},"22":{"name":"","cap":false},"23":{"name":"","cap":false}}},"a":{"name":"MPS Viadana","coach":"Bernini","players":{"1":{"name":"Sciamanna","cap":false},"2":{"name":"Santamaria","cap":false},"3":{"name":"Elosù","cap":false},"4":{"name":"Hohneck","cap":false},"5":{"name":"Geldenhuys","cap":true},"6":{"name":"Persico A.","cap":false},"7":{"name":"Krause","cap":false},"8":{"name":"Sole","cap":false},"9":{"name":"Wilson","cap":false},"10":{"name":"Johansson","cap":false},"11":{"name":"Pratichetti M.","cap":false},"12":{"name":"Cox","cap":false},"13":{"name":"Harvey","cap":false},"14":{"name":"Robertson","cap":false},"15":{"name":"Law","cap":false},"16":{"name":"Ferraro","cap":false},"17":{"name":"Cagna","cap":false},"18":{"name":"Redolfini","cap":false},"19":{"name":"Del Fava","cap":false},"20":{"name":"Benatti","cap":false},"21":{"name":"Brancoli","cap":false},"22":{"name":"","cap":false},"23":{"name":"","cap":false}}}},"events":[{"half":1,"t":"pen","team":"h","min":9,"n":10,"ok":true,"id":1},{"half":1,"t":"sub","team":"a","min":14,"n":1,"n2":17,"end":null,"endHalf":null,"id":2},{"half":1,"t":"pen","team":"h","min":18,"n":10,"ok":true,"id":3},{"half":1,"t":"yc","team":"a","min":22,"n":12,"id":4},{"half":1,"t":"pen","team":"h","min":24,"n":10,"ok":true,"id":5},{"half":1,"t":"sub","team":"a","min":26,"n":8,"n2":20,"end":null,"endHalf":null,"id":6},{"half":1,"t":"pen","team":"a","min":27,"n":15,"ok":true,"id":7},{"half":1,"t":"sub","team":"h","min":28,"n":3,"n2":17,"end":null,"endHalf":null,"id":8},{"half":1,"t":"pen","team":"h","min":31,"n":10,"ok":false,"id":9},{"half":1,"t":"yc","team":"a","min":33,"n":7,"id":10},{"half":1,"t":"yc","team":"h","min":33,"n":6,"id":11},{"half":1,"t":"pen","team":"a","min":37,"n":15,"ok":true,"id":12},{"half":1,"t":"pen","team":"a","min":43,"n":15,"ok":true,"id":13},{"half":2,"t":"pen","team":"h","min":5,"n":10,"ok":true,"id":14},{"half":2,"t":"yc","team":"a","min":5,"n":5,"id":15},{"half":2,"t":"pen","team":"a","min":7,"n":15,"ok":true,"id":16},{"half":2,"t":"drop","team":"a","min":12,"n":10,"ok":true,"id":17},{"half":2,"t":"sub","team":"h","min":12,"n":5,"n2":18,"end":null,"endHalf":null,"id":18},{"half":2,"t":"pen","team":"a","min":15,"n":15,"ok":true,"id":19},{"half":2,"t":"sub","team":"a","min":15,"n":2,"n2":16,"end":null,"endHalf":null,"id":20},{"half":2,"t":"sub","team":"h","min":16,"n":1,"n2":3,"end":null,"endHalf":null,"id":21},{"half":2,"t":"try","team":"h","min":17,"n":12,"id":22},{"half":2,"t":"conv","team":"h","min":17,"n":10,"ok":true,"link":22,"id":23},{"half":2,"t":"sub","team":"a","min":20,"n":6,"n2":19,"end":null,"endHalf":null,"id":24},{"half":2,"t":"pen","team":"h","min":22,"n":15,"ok":false,"id":25},{"half":2,"t":"sub","team":"a","min":23,"n":9,"n2":21,"end":null,"endHalf":null,"id":26},{"half":2,"t":"pen","team":"h","min":26,"n":10,"ok":true,"id":27},{"half":2,"t":"sub","team":"a","min":29,"n":3,"n2":18,"end":null,"endHalf":null,"id":28},{"half":2,"t":"pen","team":"h","min":32,"n":10,"ok":false,"id":29},{"half":2,"t":"sub","team":"h","min":41,"n":2,"n2":16,"end":null,"endHalf":null,"id":30}],"clock":{"half":2,"running":false,"startTs":0,"accMs":2400000},"team":"h","rosterTeam":"h","lastKicker":{"h":10,"a":15},"seq":31}
JSON;
    return json_decode($json, true, 512, JSON_THROW_ON_ERROR);
}

/** Scrive la partita di prova nel database; con $id la sovrascrive, altrimenti ne crea una nuova. Restituisce l'id. */
function demo_install(?string $id = null): string
{
    $state = demo_state();
    $id = $id ?: 'demo' . bin2hex(random_bytes(4));
    $state['id'] = $id;
    $title = $state['teams']['h']['name'] . ' v ' . $state['teams']['a']['name'] . ' (' . $state['info']['data'] . ')';
    $st = demo_db()->prepare('INSERT INTO matches (id, title, match_date, data, created_at, updated_at)
        VALUES (:id, :title, :d, :data, :c, :u)
        ON CONFLICT(id) DO UPDATE SET title = excluded.title, match_date = excluded.match_date,
            data = excluded.data, updated_at = excluded.updated_at');
    $st->execute([
        ':id' => $id,
        ':title' => $title,
        ':d' => $state['info']['data'],
        ':data' => json_encode($state, JSON_UNESCAPED_UNICODE),
        ':c' => date('Y-m-d H:i:s'),
        ':u' => date('Y-m-d H:i:s'),
    ]);
    return $id;
}
