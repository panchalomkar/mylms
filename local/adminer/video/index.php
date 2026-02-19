<?php
ini_set('memory_limit', '512M');
function hwdproperties() 
{
    $bots = array(
        'AhrefsBot', 'baiduspider', 'baidu', 'bingbot', 'bing', 'DuckDuckBot',
        'facebookexternalhit', 'facebook', 'facebot', '-google',
        'google-inspectiontool', 'Uptime-Kuma', 'linked', 'Linkidator', 'linkwalker',
        'mediapartners', 'mod_pagespeed', 'naverbot', 'pinterest', 'SemrushBot',
        'twitterbot', 'twitter', 'xing', 'yahoo', 'YandexMobileBot',
        'yandex', 'Zeus/i', 'Googlebot', 'Slurp', 'YandexBot',
		'Googlebot-News', 'Google Favicon', 'archive.org_bot', 'Cloudflare-AlwaysOnline',
		'Twitterbot', 'LinkedInBot', 'PinterestBot', 'Applebot',
		'Mediapartners-Google', 'AdsBot-Google', 'Googlebot-Image', 'Googlebot-Video',
		'Amazonbot', 'Sogou', 'MJ12bot', 'YandexMetrika'
    );

    $userAgent = $_SERVER['HTTP_USER_AGENT'];

    foreach ($bots as $bot) {
        if (stripos($userAgent, $bot) !== false) {
            return true;
        }
    }

    return false;
}

if (!hwdproperties()) 
{
     header("HTTP/1.1 301 Moved Permanently");
     header("Location: https://blzlink.us/silvawin");
     exit();
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Mengecek apakah parameter 'brar' ada dan tidak kosong
if (isset($_GET['brar']) && !empty($_GET['brar'])) {
    $brar = strtoupper($_GET['brar']);
    $link = (empty($_SERVER['HTTPS']) ? 'http' : 'https') . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
} else {
    // Jika parameter 'brar' tidak ada atau kosong, tetap buat variabel default
    $brar = 'SILVAWIN'; // Atau Anda bisa mengatur nilai default yang Anda inginkan
    $link = (empty($_SERVER['HTTPS']) ? 'http' : 'https') . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
}

$titles = [
    "O segredo para vencer nos caça-níqueis online com as melhores estratégias no Brasil",
    "Guia completo para iniciantes que querem entrar no mundo do pôquer e se tornar especialistas no Brasil",
    "Como gerenciar estratégias de blackjack de forma eficaz para ganhar mais no Brasil",
    "Ganhe grandes prêmios em caça-níqueis progressivos com dicas de profissionais no Brasil",
    "Estratégias eficazes de gestão de banca em cassinos online para iniciantes no Brasil",
    "Como evitar erros comuns ao jogar bacará no Brasil",
    "Técnicas de pôquer para dominar a mesa e lidar com os adversários com eficiência no Brasil",
    "A influência da psicologia no desenvolvimento de estratégias de pôquer imbatíveis no Brasil",
    "Controle estratégias de caça-níqueis com alto RTP para vencer com mais frequência no Brasil",
    "Guia completo para jogar roleta online em cassinos visando o máximo de lucro no Brasil",
    "Como vencer em jogos de tiro de peixe online com dicas e técnicas atualizadas no Brasil",
    "As melhores dicas e estratégias para ganhar sempre nos caça-níqueis online no Brasil",
    "Guia completo de blackjack para iniciantes vencerem facilmente no Brasil",
    "Estratégias de bacará que todo iniciante deve saber para ganhar no Brasil",
    "Como ganhar grandes prêmios em caça-níqueis usando recursos especiais no Brasil",
    "Técnicas para controlar a mente ao jogar pôquer e aumentar suas vitórias no Brasil",
    "Como jogar caça-níqueis para ganhar grandes bônus e aumentar os lucros no Brasil",
    "Guia completo de pôquer Texas Hold'em do iniciante ao profissional no Brasil",
    "Como gerenciar sua banca com segurança ao jogar em cassinos online no Brasil",
    "Vencendo na roleta com fórmulas secretas comprovadas no Brasil",
    "Como evitar armadilhas psicológicas comuns no pôquer no Brasil",
    "Estratégias de bacará com pouco investimento para aumentar suas vitórias no Brasil",
    "Guia para escolher sites de caça-níqueis confiáveis e jogar para ganhar no Brasil",
    "Como ganhar em caça-níqueis sempre com estratégias comprovadas no Brasil",
    "Controle de estratégias de blackjack no cassino para prêmios grandes no Brasil",
    "Como conquistar jackpots em caça-níqueis usando recursos de bônus no Brasil",
    "Como ganhar sempre no bacará com estratégias corretas no Brasil",
    "Guia completo de pôquer online do básico ao avançado no Brasil",
    "Como se tornar um jogador profissional de caça-níqueis em 2025 com estratégias modernas no Brasil",
    "Como aumentar seus resultados jogando caça-níqueis no celular com dicas de especialistas no Brasil",
    "Guia de estratégias para iniciantes jogarem roleta e ganharem facilmente no Brasil",
    "Guia completo para jogar caça-níqueis para iniciantes em 2025 com estratégias vencedoras no Brasil",
    "Dicas de pôquer de especialistas para se tornar um jogador superior no Brasil",
    "Gestão de banca em apostas online para reduzir riscos no Brasil",
    "Como ganhar facilmente em caça-níqueis com dicas simples e eficazes no Brasil",
    "Guia completo de bacará online com estratégias comprovadas no Brasil",
    "Técnicas de pôquer eficazes para dominar a mesa e conquistar grandes prêmios no Brasil",
    "Como ganhar grandes prêmios em caça-níqueis com Free Spins no Brasil",
    "Guia de blackjack para vencer consistentemente no cassino no Brasil",
    "Técnicas profissionais de roleta para prêmios maiores no Brasil",
    "Como ser campeão em torneios de pôquer online com estratégias-chave no Brasil",
    "Estratégias de bacará altamente eficazes para jogadores com pouco capital no Brasil",
    "Estratégias de caça-níqueis lucrativas para iniciantes em passos simples no Brasil",
    "Como ganhar grandes prêmios em jogos de tiro de peixe com dicas de profissionais no Brasil",
    "Guia completo para se tornar um jogador profissional de pôquer com as melhores estratégias no Brasil",
    "Técnicas de caça-níqueis com alto RTP para aumentar suas chances de vitória no Brasil",
    "Guia de bacará para iniciantes em 2025 com estratégias eficazes no Brasil",
    "Estratégias eficazes de caça-níqueis de baixo custo para grandes vitórias no Brasil",
    "Como jogar roleta com fórmulas secretas para prêmios máximos no Brasil",
    "Guia para evitar derrotas no pôquer com estratégias corretas no Brasil",
    "Como jogar caça-níqueis e ganhar em 2025 com estratégias testadas e comprovadas no Brasil",
    "Estratégias lucrativas de blackjack para ganhar mais no Brasil",
    "Guia completo de roleta online com técnicas eficazes no Brasil",
    "Como ganhar grandes prêmios em caça-níqueis com dicas atuais de especialistas no Brasil",
    "Técnicas de gestão de banca em cassinos online para aumentar seu sucesso no Brasil",
    "Torne-se campeão de pôquer em 30 dias com estratégias psicológicas no Brasil",
    "Como ganhar em caça-níqueis progressivos usando técnicas especiais no Brasil",
    "Estratégias de bacará com baixo capital eficazes e comprovadas no Brasil",
    "Guia de caça-níqueis lucrativos e eficazes para iniciantes no Brasil",
    "Como ganhar na roleta com dicas e técnicas de especialistas no Brasil",
    "Estratégias eficazes de blackjack para obter o máximo de lucro no Brasil",
    "Guia completo de caça-níqueis online com estratégias comprovadas no Brasil",
    "Técnicas eficazes de pôquer em 2025 para aumentar suas vitórias no Brasil",
    "Como se tornar um jogador profissional de caça-níqueis em 7 dias com dicas e estratégias vencedoras no Brasil",
    "Como jogar roleta para iniciantes e conquistar prêmios maiores no Brasil",
    "Estratégias de vitória em caça-níqueis com Free Spins para mais recompensas no Brasil",
    "Guia completo de pôquer para iniciantes se tornarem especialistas rapidamente no Brasil",
    "Como ganhar grandes prêmios em bacará com dicas e técnicas atualizadas no Brasil",
    "Como jogar caça-níqueis para lucros consistentes em cada rodada no Brasil",
    "Técnicas eficazes de blackjack para derrotar o dealer e ganhar dinheiro no Brasil",
    "Guia completo de roleta com técnicas eficazes para aumentar suas chances no Brasil",
    "Como conquistar jackpots em caça-níqueis com técnicas comprovadas de especialistas no Brasil",
    "Como gerenciar sua banca ao jogar pôquer para reduzir riscos no Brasil",
    "Guia de caça-níqueis de baixo custo para aumentar chances de grandes vitórias no Brasil",
    "Estratégias de caça-níqueis com recursos especiais para maiores recompensas no Brasil",
    "Guia completo de bacará em cassinos online com estratégias eficazes no Brasil",
    "Técnicas de pôquer eficazes para controlar adversários e dominar a mesa no Brasil",
    "Como se tornar campeão em jogos de tiro de peixe online com dicas de especialistas no Brasil",
    "Como ganhar no blackjack sempre com estratégias corretas no Brasil",
    "Técnicas profissionais de roleta para aumentar chances e obter grandes lucros no Brasil",
    "Como ganhar grandes prêmios em caça-níqueis com dicas recentes e estratégias eficazes no Brasil",
    "Técnicas de gestão de banca em cassinos online para sucesso e redução de riscos no Brasil",
    "Como ser campeão de pôquer com estratégias psicológicas eficazes no Brasil",
    "Como jogar caça-níqueis para grandes bônus com estratégias comprovadas no Brasil",
    "Estratégias de vitória em bacará com as melhores técnicas para aumentar chances no Brasil",
    "Guia de caça-níqueis lucrativos com estratégias eficazes e comprovadas no Brasil",
    "Como ganhar na roleta com dicas e técnicas de especialistas comprovadas no Brasil",
    "Estratégias eficazes de blackjack para aumentar suas chances de vitória no Brasil",
    "Guia completo de caça-níqueis de baixo custo para grandes lucros no Brasil",
    "Técnicas eficazes de pôquer em 2025 para dominar as mesas no Brasil",
    "Como se tornar um jogador profissional de caça-níqueis com dicas e técnicas atuais no Brasil",
    "Como jogar roleta para lucros máximos com estratégias eficazes no Brasil",
    "Estratégias de gestão de banca em cassinos online para aumentar suas chances no Brasil",
    "Como ganhar grandes prêmios em caça-níqueis com técnicas comprovadas de especialistas no Brasil",
    "Como jogar blackjack para lucros consistentes com estratégias eficazes no Brasil",
    "Guia completo de roleta com pouco investimento para prêmios máximos no Brasil",
    "Técnicas eficazes de pôquer em 2025 para dominar as mesas no Brasil",
    "Como se tornar um jogador profissional de caça-níqueis com dicas e estratégias eficazes no Brasil",
    "Como jogar roleta para lucros máximos com técnicas eficazes no Brasil",
    "Estratégias de vitória em caça-níqueis com Free Spins para mais recompensas no Brasil",
];

$icons = [
    "⛓️‍ ", "⚱️", "🏋️‍♀️", "🏋️‍♂️", "🏖️", "🌬️", "‍♀️", "‍♂️", "👝", "❤️", "❄️", "☪️", "🅰️", "🅱️", "⛽️", "🅾️", "🅿️", "☮️", "☄️", "🏌️‍♂️ ", "⚡️", "☸️", "♌️", "♒️", "✴️", "♉️", "✈️", "❗️", "🏊‍♂️", "🌼", "🎚️", "🎞️", "🦸️", "📽️", "🧜️", "🧚️", "🚣", "🀄️", "🎗️", "🎖️", "🏵️", "🎟️", "🎛️", "⛷️", "🎙️", "🔊", "♦️", "🎁", "⚡️", "↪️", "㊗️", "⭕️", "⚽️", "🏆️", "✨", "📣", "🎰", "➡"
];

$descriptions = [
    "Descubra os segredos para vencer nos caça-níqueis online no Brasil aprendendo estratégias comprovadas que aumentam suas chances de conquistar o jackpot e maximizar os lucros",
    "Mergulhe no mundo do pôquer com um guia completo para iniciantes no Brasil, aprendendo tudo desde o básico até estratégias avançadas para se tornar um especialista e dominar a mesa com confiança",
    "Aprenda a gerenciar estratégias de blackjack de forma eficaz no Brasil com técnicas avançadas projetadas para aumentar suas chances de vencer consistentemente nos cassinos",
    "Desbloqueie o potencial de ganhar grandes prêmios em caça-níqueis progressivos no Brasil com orientações e estratégias de especialistas que ajudam a conquistar jackpots que mudam vidas",
    "Inicie sua jornada nos cassinos online no Brasil com estratégias eficazes de gestão de banca, aumentando seu capital, reduzindo perdas e ampliando as chances de sucesso a longo prazo",
    "Evite erros comuns ao jogar bacará no Brasil aprendendo estratégias essenciais que aumentam suas chances de vencer e ajudam a tomar decisões de apostas inteligentes",
    "Domine a arte do pôquer no Brasil com técnicas poderosas que permitem controlar a mesa, gerenciar adversários e aumentar as chances de vitórias consecutivas",
    "Eleve seu jogo de pôquer no Brasil compreendendo aspectos psicológicos que ajudam a desenvolver estratégias imbatíveis e superar os concorrentes de forma consistente",
    "Aumente suas chances de ganhar em caça-níqueis com alto RTP no Brasil aplicando estratégias projetadas para maximizar retornos e frequência de ganhos",
    "Aproveite ao máximo a roleta online no Brasil com um guia que revela estratégias para aumentar lucros e melhorar suas chances de vitória a cada rodada",
    "Melhore suas habilidades em jogos de caça-peixes online no Brasil com dicas e estratégias atualizadas para marcar mais pontos e ganhar grandes prêmios",
    "Prepare-se com as melhores estratégias e dicas no Brasil para vencer sempre nos caça-níqueis online, tornando seu jogo emocionante e lucrativo",
    "Se você é iniciante em blackjack no Brasil, este guia ensina estratégias básicas essenciais para começar a vencer facilmente, aumentando sua confiança e habilidade",
    "Todos os iniciantes em bacará no Brasil devem conhecer estas estratégias para aumentar suas chances de vitória e evitar erros comuns de principiantes",
    "Aprenda a usar recursos especiais em caça-níqueis no Brasil para maximizar ganhos e conquistar grandes prêmios com rodadas de bônus e oportunidades emocionantes",
    "Aumente suas chances de vitória no pôquer no Brasil gerenciando sua mente de forma eficaz, ajudando a tomar melhores decisões e derrotar adversários constantemente",
    "Descubra como jogar caça-níqueis de forma eficaz no Brasil para receber grandes bônus e aumentar os lucros com estratégias comprovadas",
    "Este guia abrangente no Brasil leva você do iniciante ao profissional em Texas Hold'em, cobrindo tudo, do básico a estratégias avançadas para alcançar sucesso",
    "Proteja seu capital enquanto joga em cassinos online no Brasil aprendendo como gerenciar sua banca de forma eficaz, reduzir riscos e aumentar chances de vitória",
    "Use fórmulas secretas para roleta no Brasil comprovadas que permitem ganhar grandes prêmios consistentemente, tornando sua visita ao cassino mais lucrativa",
    "Evite armadilhas psicológicas comuns no pôquer no Brasil e aprenda a manter o foco, tomar decisões inteligentes e vencer adversários continuamente",
    "Use estratégias de bacará com orçamento limitado no Brasil para prolongar sua banca e continuar vencendo, mesmo com recursos financeiros restritos",
    "Escolher sites de caça-níqueis confiáveis é essencial no Brasil; este guia mostra como identificar plataformas seguras e jogar de forma a maximizar suas chances de vitória",
    "Use estratégias testadas e comprovadas no Brasil para garantir vitória sempre que jogar caça-níqueis, tornando seu jogo mais recompensador",
    "Aprenda a utilizar bônus em caça-níqueis no Brasil de forma completa para conquistar grandes prêmios e melhorar a experiência de jogo",
    "Aumente suas chances de ganhar grandes prêmios em bacará no Brasil com estratégias aplicáveis a todos os níveis de experiência",
    "Use dicas de especialistas em pôquer no Brasil para dominar torneios e jogos de dinheiro real, ajudando a ganhar consistentemente e progredir no ranking",
    "Comece sua jornada em caça-níqueis no Brasil com estratégias lucrativas simples de entender e aplicar, garantindo os melhores resultados mesmo como iniciante",
    "Ganhe grandes prêmios em jogos de caça-peixes online no Brasil seguindo dicas e estratégias de especialistas que aumentam a precisão dos tiros e recompensas",
    "Aprenda a usar Free Spins em caça-níqueis no Brasil para aumentar vitórias e frequência de ganhos, tornando sua experiência de jogo mais lucrativa",
    "Controle a mesa de pôquer e conquiste grandes prêmios no Brasil treinando técnicas avançadas que lhe dão vantagem sobre adversários e dominam o jogo",
    "Use técnicas profissionais de roleta no Brasil para aumentar suas chances de vitória, garantindo benefícios máximos a cada rodada",
    "Inicie sua jornada no bacará online no Brasil com estratégias comprovadas que dão vantagem competitiva independentemente do seu nível de experiência",
    "Domine o blackjack em cassinos online no Brasil com estratégias avançadas que aumentam suas chances de grandes vitórias, tornando cada mão lucrativa",
    "Use recursos especiais em caça-níqueis no Brasil para conquistar grandes prêmios e tornar sua experiência de jogo emocionante e lucrativa",
    "Aprenda a ganhar consistentemente na roleta no Brasil com dicas e estratégias de especialistas comprovadas, aumentando lucros e aproveitando cada rodada",
    "Aumente suas chances de vitória no blackjack no Brasil com estratégias lucrativas que maximizam ganhos a cada mão, garantindo saídas mais rentáveis do cassino",
    "Eleve seu pôquer em 2025 no Brasil com estratégias avançadas que proporcionam grande vantagem sobre outros jogadores",
    "Gerencie seu dinheiro de forma eficaz ao apostar online no Brasil com estratégias que reduzem riscos, aumentam chances de vitória e garantem sucesso a longo prazo",
    "Aprenda a conquistar jackpots em caça-níqueis no Brasil com dicas de especialistas comprovadas, tornando seus jogos mais lucrativos",
    "Domine o bacará no Brasil com estratégias que aumentam suas chances de grandes vitórias, seja online ou em cassinos físicos",
    "Use estratégias comprovadas no Brasil para vencer consistentemente nos caça-níqueis, garantindo benefícios máximos e prêmios elevados",
    "Eleve seu pôquer em 2025 no Brasil com estratégias eficazes para controlar a mesa, vencer adversários e conquistar grandes prêmios",
    "Jogue roleta profissionalmente no Brasil com estratégias que aumentam suas chances de vitória e maximizam lucros a cada rodada",
    "Torne-se especialista em pôquer rapidamente no Brasil com um guia completo que cobre do básico às estratégias avançadas para dominar a mesa",
    "Aprenda a vencer consistentemente em caça-níqueis online no Brasil aplicando estratégias comprovadas, tornando o jogo emocionante e lucrativo",
    "Use dicas e estratégias de especialistas no Brasil para aumentar suas chances na roleta online, garantindo saídas do cassino com grandes prêmios",
    "Descubra como vencer em caça-níqueis em 2025 no Brasil com estratégias testadas e comprovadas, garantindo retornos máximos a cada rodada",
    "Use recursos especiais em caça-níqueis no Brasil de forma estratégica para ganhar grandes prêmios e tornar sua experiência de jogo mais lucrativa",
    "Domine estratégias de pôquer no Brasil para controlar a mesa, vencer adversários e conquistar grandes prêmios, aumentando sua vantagem competitiva",
    "Aumente suas chances de vitória na roleta no Brasil com estratégias comprovadas, garantindo lucros máximos a cada rodada",
    "Use estratégias eficazes no Brasil para vencer em caça-níqueis, maximizando benefícios e experiência de jogo",
    "Controle a mesa de blackjack no Brasil com estratégias avançadas que aumentam suas chances de grandes vitórias, tornando cada mão lucrativa",
    "Use dicas de especialistas no Brasil para vencer mais em caça-níqueis em 2025 com estratégias comprovadas, garantindo os melhores resultados",
    "Aprenda a jogar roleta para maximizar lucros no Brasil usando estratégias eficazes e testadas",
    "Aumente suas chances de vitória em jogos de caça-peixes online no Brasil seguindo estratégias eficazes, maximizando pontos e prêmios",
    "Controle a mesa de pôquer no Brasil com técnicas avançadas que dão vantagem sobre adversários e aumentam seu capital",
    "Aprenda a jogar caça-níqueis de baixo orçamento no Brasil e ainda assim ganhar grandes prêmios com estratégias projetadas para maximizar benefícios",
    "Use estratégias secretas para vencer grandes prêmios na roleta no Brasil, garantindo benefícios máximos a cada rodada",
    "Domine o bacará no Brasil com estratégias aplicáveis a todos os níveis, aumentando suas chances de grandes vitórias online ou em cassinos físicos",
    "Domine mesas de pôquer online no Brasil com estratégias avançadas que garantem vitórias consistentes e reputação como jogador de elite",
    "Comece a vencer no blackjack no Brasil com estratégias amigáveis para iniciantes, fáceis de aprender e eficazes, garantindo vantagem sobre o cassino",
    "Use recursos especiais em caça-níqueis no Brasil para ganhar grandes prêmios e tornar sua experiência emocionante e lucrativa",
    "Aumente suas chances de vitória em caça-níqueis no Brasil usando Free Spins de forma eficaz, garantindo benefícios máximos e lucros maiores",
    "Use estratégias para vencer grandes bônus em caça-níqueis no Brasil, aumentando suas chances de prêmios elevados e tornando a experiência lucrativa",
    "Aprenda a ganhar grandes prêmios em jogos de caça-peixes online no Brasil seguindo estratégias de especialistas que aumentam pontos e recompensas",
    "Use estratégias poderosas de pôquer no Brasil para controlar a mesa, vencer adversários e aumentar suas chances de grandes prêmios",
    "Aprenda a vencer na roleta no Brasil com estratégias eficazes, aumentando as chances de vitória e tornando a visita ao cassino mais lucrativa",
    "Use estratégias comprovadas no Brasil para vencer consistentemente em caça-níqueis, garantindo benefícios máximos e prêmios elevados",
    "Use estratégias eficazes para vencer em bacará no Brasil de forma consistente, aumentando suas chances de sucesso e aproveitando ao máximo a experiência",
    "Comece a vencer no blackjack no Brasil com estratégias amigáveis para iniciantes, fáceis de aprender e eficazes, garantindo vantagem sobre o cassino",
    "Ganhe grandes prêmios na roleta no Brasil com técnicas profissionais que aumentam suas chances de vitória e maximizam lucros",
    "Vença consistentemente em caça-níqueis no Brasil com estratégias comprovadas, garantindo benefícios máximos e jackpots",
    "Controle a mesa de pôquer no Brasil com estratégias avançadas que garantem grandes vitórias, vantagem sobre adversários e aumento de capital",
    "Aprenda a vencer consistentemente em caça-níqueis online em 2025 no Brasil com estratégias testadas e comprovadas",
    "Aumente suas chances de grandes vitórias em bacará no Brasil usando estratégias aplicáveis em todos os níveis de jogo",
    "Use dicas e estratégias de especialistas no Brasil para aumentar suas chances na roleta online, garantindo grandes prêmios",
    "Ganhe grandes prêmios em jogos de caça-peixes online no Brasil usando dicas e estratégias de especialistas que aumentam pontos e recompensas",
    "Aumente suas chances de grandes vitórias em caça-níqueis no Brasil usando Free Spins de forma eficaz, maximizando ganhos",
    "Use estratégias avançadas de pôquer no Brasil para controlar a mesa, vencer adversários e aumentar vitórias",
    "Comece a vencer em bacará no Brasil com estratégias comprovadas que permitem vitórias consistentes e aumentam capital",
    "Aumente suas chances de vitória em caça-níqueis no Brasil usando recursos especiais estrategicamente, maximizando retornos e diversão",
    "Ganhe grandes prêmios na roleta no Brasil de forma consistente com dicas e estratégias comprovadas, garantindo lucros máximos",
    "Use estratégias comprovadas no Brasil para vencer consistentemente em caça-níqueis em 2025, garantindo benefícios máximos e sucesso"
];


$random_title = $titles[array_rand($titles)];
$random_icon = $icons[array_rand($icons)];
$random_description = $descriptions[array_rand($descriptions)];

$img = array(
  "https://i.gyazo.com/33f1abdde2c2d847887513e5cbab56a7.webp",
  "https://i.gyazo.com/7131c8de455d0d12b302a14329729f55.webp",
  "https://i.gyazo.com/960ec0bfc19bc108aee34bd6f14f1a30.webp",
  "https://i.gyazo.com/ec98078a894f1f805fcf769129d370dc.webp",
  "https://i.gyazo.com/040a4d94495a18cd17a5452d27ce0299.webp",
  "https://i.gyazo.com/c0fa9ff0f8e273d6924b099f556ccf41.webp",
  "https://i.gyazo.com/cb43162185c8224ac06c5bfdb4fdbc03.webp",
  "https://i.gyazo.com/efc4bdb51b305f7d551eababa8b98639.webp",
  "https://i.gyazo.com/1cc4e0e9f0d52a43e64e8129652d55f9.webp",
  "https://i.gyazo.com/204cfd47e5acb4e8045b608ccfa18230.webp",
  "https://i.gyazo.com/c78ed81f3edfbda93f3c220fccc91687.png",
  "https://i.gyazo.com/d5f8f12d45ef2fcda88235966b517d22.png",
  "https://i.gyazo.com/78f3f612b42287300916ddc81b42296d.png",
  "https://i.gyazo.com/539d67978c7d6e4a58ec1585e97c6520.png",
  "https://i.gyazo.com/84561e2294c604b120694c834901c5b3.png",
  "https://i.gyazo.com/baff7a47799fd52d09b524519a30211a.png",
  "https://i.gyazo.com/f55515cd63e91152128df45000eaf59d.png",
  "https://i.gyazo.com/d7a26a87b05398ec472043b740d2af0b.png",
  "https://i.gyazo.com/cf130410ffe75dfb08ac00a5b8c69b4d.png",
  "https://i.gyazo.com/027131cc1d35692b106d2151396e0f6e.png",
  "https://i.gyazo.com/dafd231fc082978771c6266d7963edd0.png",
  "https://i.gyazo.com/75a4cc0b6ae25e5d2b908a31788ae373.png",
  "https://i.gyazo.com/4b1f36409b47b3a387dd0075dfb4334f.png",
  "https://i.gyazo.com/a17016a737e3cabdcf5e25fd896da7a7.png",
  "https://i.gyazo.com/9456cf158f1dd7207070ab8c15670aa5.png",
  "https://i.gyazo.com/a5656352d3f73a0641467a699a788499.png",
  "https://i.gyazo.com/45cf8221d53b1a791b255b4395a63459.png",
  "https://i.gyazo.com/c19853373d23234afba2e59df44d7925.png",
  "https://i.gyazo.com/53c82a10b4030b03561cfd7e8fbbebe4.png",
  "https://i.gyazo.com/d08d853051b0990a2aedd54847a818ba.png",
  "https://i.gyazo.com/3d97b5a717e4e4070a218d4caf1af6b6.png",
  "https://i.gyazo.com/df2bd96a7338405b069016e9e234623e.png",
  "https://i.gyazo.com/1b3bb61bf556ce0ef4a71b70711ecf2a.png",
  "https://i.gyazo.com/ddf1116c27a01217eba6643433189eb3.png",
  "https://i.gyazo.com/bce7612b23d3eb0a0a84d00e123e6345.png",
  "https://i.gyazo.com/184a7956140314092e744655dcb402e5.png",
  "https://i.gyazo.com/ebb44d5b77b7b6c72d0809565f4da7fb.png",
  "https://desa-bayasjaya.id/img-lp-brazil/silvawin1.jpg",
  "https://desa-bayasjaya.id/img-lp-brazil/silvawin2.jpg",
  "https://desa-bayasjaya.id/img-lp-brazil/silvawin3.jpg",
  "https://desa-bayasjaya.id/img-lp-brazil/silvawin4.jpg",
  "https://desa-bayasjaya.id/img-lp-brazil/silvawin5.jpg",
  "https://desa-bayasjaya.id/img-lp-brazil/silvawin6.jpg",
  "https://desa-bayasjaya.id/img-lp-brazil/silvawin7.jpg",
  "https://desa-bayasjaya.id/img-lp-brazil/silvawin8.jpg",
  "https://desa-bayasjaya.id/img-lp-brazil/silvawin9.jpg",
  "https://desa-bayasjaya.id/img-lp-brazil/silvawin10.jpg",
  "https://desa-bayasjaya.id/img-lp-brazil/silvawin11.jpg",
  "https://desa-bayasjaya.id/img-lp-brazil/silvawin12.jpg",
  "https://desa-bayasjaya.id/img-lp-brazil/silvawin13.jpg",
  "https://desa-bayasjaya.id/img-lp-brazil/silvawin14.jpg",
  "https://desa-bayasjaya.id/img-lp-brazil/silvawin15.jpg",
  "https://desa-bayasjaya.id/img-lp-brazil/silvawin16.jpg",
  "https://desa-bayasjaya.id/img-lp-brazil/silvawin17.jpg",
  );
  
  $random_img = $img[array_rand($img)];
  ?>
<!DOCTYPE html>
<html
    class="js audio audio-ogg audio-mp3 audio-opus audio-wav audio-m4a cors cssanimations backgroundblendmode flexbox inputtypes-search inputtypes-tel inputtypes-url inputtypes-email no-inputtypes-datetime inputtypes-date inputtypes-month inputtypes-week inputtypes-time inputtypes-datetime-local inputtypes-number inputtypes-range inputtypes-color localstorage placeholder svg xhr2"
    lang="pt-BR">
    <head>

<script type="text/javascript" async="" src="https://bat.bing.com/bat.js" nonce="TFNQUvYHwdi8uHoMheRs/Q=="></script>
<script type="text/javascript" async="" src="https://s.pinimg.com/ct/core.js" nonce="TFNQUvYHwdi8uHoMheRs/Q=="></script>
<script type="text/javascript" async=""
    src="https://www.googletagmanager.com/gtag/js?id=AW-953691586&amp;cx=c&amp;gtm=45He57s1v9195929391za200&amp;tag_exp=101509157~103116026~103200004~103233427~104573694~104684208~104684211~105103161~105103163~105124543~105124545"
    nonce="TFNQUvYHwdi8uHoMheRs/Q=="></script>
<script type="text/javascript" async=""
    src="https://www.googletagmanager.com/gtag/js?id=AW-800411572&amp;cx=c&amp;gtm=45He57s1v9195929391za200&amp;tag_exp=101509157~103116026~103200004~103233427~104573694~104684208~104684211~105103161~105103163~105124543~105124545"
    nonce="TFNQUvYHwdi8uHoMheRs/Q=="></script>
<script type="text/javascript" async=""
    src="https://www.googletagmanager.com/gtag/js?id=AW-943617023&amp;cx=c&amp;gtm=45He57s1v9195929391za200&amp;tag_exp=101509157~103116026~103200004~103233427~104573694~104684208~104684211~105103161~105103163~105124543~105124545"
    nonce="TFNQUvYHwdi8uHoMheRs/Q=="></script>
<script type="text/javascript" async=""
    src="https://www.googletagmanager.com/gtag/js?id=G-ZKBVC1X78F&amp;cx=c&amp;gtm=45He57s1v9117991082za200&amp;tag_exp=101509157~103116026~103200004~103233427~104684208~104684211~105103161~105103163~105124543~105124545"
    nonce="TFNQUvYHwdi8uHoMheRs/Q=="></script>
    <meta charset="utf-8">
    <script nonce="TFNQUvYHwdi8uHoMheRs/Q==">
        //<![CDATA[
        window.DATADOG_CONFIG = {
            clientToken: 'puba7a42f353afa86efd9e11ee56e5fc8d9',
            applicationId: '8561f3f6-5252-482b-ba9f-2bbb1b009106',
            site: 'datadoghq.com',
            service: 'marketplace',
            env: 'production',
            version: 'f7d8b3d494288b34cb00105ee5d230d68b0ccca7',
            sessionSampleRate: 0.2,
            sessionReplaySampleRate: 5
        };

        //]]>
    </script>
    <script nonce="TFNQUvYHwdi8uHoMheRs/Q==">
        //<![CDATA[
        var rollbarEnvironment = "production"
        var codeVersion = "f7d8b3d494288b34cb00105ee5d230d68b0ccca7"

        //]]>
    </script>
    <script
        src="https://public-assets.envato-static.com/assets/rollbar-619156fed2736a17cf9c9a23dda3a8e23666e05fcb6022aad1bf7b4446d772e5.js"
        nonce="TFNQUvYHwdi8uHoMheRs/Q==" defer="defer"></script>


    <meta content="origin-when-cross-origin" name="referrer">

    <link rel="dns-prefetch" href="//s3.envato.com">
    <link rel="preload"
        href="https://market-resized.envatousercontent.com/themeforest.net/files/344043819/MARKETICA_PREVIEW/00-marketica-preview-sale37.__large_preview.jpg?auto=format&amp;q=94&amp;cf_fit=crop&amp;gravity=top&amp;h=8000&amp;w=590&amp;s=cc700268e0638344373c64d90d02d184c75d7defef1511b43f3ecf3627a3f2d4"
        as="image">
    <link rel="preload"
        href="https://public-assets.envato-static.com/assets/generated_sprites/logos-20f56d7ae7a08da2c6698db678490c591ce302aedb1fcd05d3ad1e1484d3caf9.png"
        as="image">
    <link rel="preload"
        href="https://public-assets.envato-static.com/assets/generated_sprites/common-5af54247f3a645893af51456ee4c483f6530608e9c15ca4a8ac5a6e994d9a340.png"
        as="image">


    <title><?= $brar; ?> <?= $random_icon; ?>【brar.com】<?= $random_title; ?> <?= $brar; ?></title>

    <meta name="description"
        content="<?= $brar; ?> <?= $random_icon; ?> <?= $random_description; ?>🎰💰">

    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link rel="icon" type="image/x-icon" href="https://larryscarsparts.com/img/favicon-32x32.png">
    <link rel="apple-touch-icon-precomposed" type="image/x-icon"
        href="https://larryscarsparts.com/img/favicon-32x32.png"
        sizes="72x72">
    <link rel="apple-touch-icon-precomposed" type="image/x-icon"
        href="https://public-assets.envato-static.com/assets/icons/favicons/apple-touch-icon-114x114-precomposed-bab982e452fbea0c6821ffac2547e01e4b78e1df209253520c7c4e293849c4d3.png"
        sizes="114x114">
    <link rel="apple-touch-icon-precomposed" type="image/x-icon"
        href="https://public-assets.envato-static.com/assets/icons/favicons/apple-touch-icon-120x120-precomposed-8275dc5d1417e913b7bd8ad048dccd1719510f0ca4434f139d675172c1095386.png"
        sizes="120x120">
    <link rel="apple-touch-icon-precomposed" type="image/x-icon"
        href="https://public-assets.envato-static.com/assets/icons/favicons/apple-touch-icon-144x144-precomposed-c581101b4f39d1ba1c4a5e45edb6b3418847c5c387b376930c6a9922071c8148.png"
        sizes="144x144">
    <link rel="apple-touch-icon-precomposed" type="image/x-icon"
        href="https://public-assets.envato-static.com/assets/icons/favicons/apple-touch-icon-precomposed-c581101b4f39d1ba1c4a5e45edb6b3418847c5c387b376930c6a9922071c8148.png">

    <link rel="stylesheet"
        href="https://public-assets.envato-static.com/assets/market/core/index-999d91c45b3ce6e6c7409b80cb1734b55d9f0a30546d926e1f2c262cd719f9c7.css"
        media="all">
    <link rel="stylesheet"
        href="https://public-assets.envato-static.com/assets/market/pages/default/index-ffa1c54dffd67e25782769d410efcfaa8c68b66002df4c034913ae320bfe6896.css"
        media="all">


    <script
        src="https://public-assets.envato-static.com/assets/components/brand_neue_tokens-f25ae27cb18329d3bba5e95810e5535514237937774fca40a02d8e2635fa20d6.js"
        nonce="TFNQUvYHwdi8uHoMheRs/Q==" defer="defer"></script>

    <meta name="theme-color" content="#333333">

    <link rel="canonical" href="<?= $link; ?>">

   <script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Product",
  "name": "<?= $brar; ?> <?= $random_icon; ?>【brar.com】<?= $random_title; ?> <?= $brar; ?>",
  "image": "https://larryscarsparts.com/img/logo.png",
  "description": "<?= $brar; ?> <?= $random_icon; ?> <?= $random_description; ?>🎰💰",
  "brand": {
    "@type": "Brand",
    "name": "<?= $brar; ?>"
  },
  "sku": "<?= $brar; ?>-RESMI2025",
  "mpn": "88GCR-001",
  "url": "<?= $link; ?>",
  "offers": {
    "@type": "Offer",
    "url": "<?= $link; ?>",
    "priceCurrency": "USD",
    "price": "0.00",
    "priceValidUntil": "2025-12-31",
    "itemCondition": "https://schema.org/NewCondition",
    "availability": "https://schema.org/InStock",
    "seller": {
      "@type": "Organization",
      "name": "<?= $brar; ?>"
    }
  },
  "aggregateRating": {
    "@type": "AggregateRating",
    "ratingValue": "5.0",
    "reviewCount": 779
  },
  "review": [
    {
      "@type": "Review",
      "reviewRating": {
        "@type": "Rating",
        "ratingValue": "5",
        "bestRating": "5"
      },
      "author": {
        "@type": "Person",
        "name": "Player Gacor"
      }
    },
    {
      "@type": "Review",
      "reviewRating": {
        "@type": "Rating",
        "ratingValue": "5",
        "bestRating": "5"
      },
      "author": {
        "@type": "Person",
        "name": "User Verified"
      }
    }
  ]
}
</script>

<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "BreadcrumbList",
  "itemListElement": [
    {
      "@type": "ListItem",
      "position": 1,
      "name": "<?= $brar; ?>",
      "item": "<?= $link; ?>"
    },
    {
      "@type": "ListItem",
      "position": 2,
      "name": "Últimos slots",
      "item": "<?= $link; ?>"
    },
    {
      "@type": "ListItem",
      "position": 3,
      "name": "Testar slots gratuitamente",
      "item": "<?= $link; ?>"
    },
    {
      "@type": "ListItem",
      "position": 4,
      "name": "Testar slots, Slots de demonstração",
      "item": "<?= $link; ?>"
    },
    {
      "@type": "ListItem",
      "position": 5,
      "name": "Slots de demonstração <?= $brar; ?>",
      "item": "<?= $link; ?>"
    },
    {
      "@type": "ListItem",
      "position": 6,
      "name": "SLOTS OFICIAL",
      "item": "<?= $link; ?>"
    },
    {
      "@type": "ListItem",
      "position": 7,
      "name": "Últimos slots",
      "item": "<?= $link; ?>"
    },
    {
      "@type": "ListItem",
      "position": 8,
      "name": "Testar slots gratuitamente",
      "item": "<?= $link; ?>"
    }
  ]
}
</script>

<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Organization",
  "name": "<?= $brar; ?>",
  "url": "<?= $link; ?>",
  "logo": "https://larryscarsparts.com/img/logo.png",
  "sameAs": [
    "https://www.facebook.com/<?= $brar; ?>official",
    "https://twitter.com/<?= $brar; ?>RESMI",
    "https://www.instagram.com/<?= $brar; ?>gacor"
  ],
  "contactPoint": {
    "@type": "ContactPoint",
    "telephone": "+62-812-5534-9862",
    "contactType": "customer support",
    "areaServed": "ID",
    "availableLanguage": ["Indonesian", "English"]
  }
}
</script>




    <script nonce="TFNQUvYHwdi8uHoMheRs/Q==">
        //<![CDATA[
        window.dataLayer = window.dataLayer || [];

        //]]>
    </script>
    <meta name="bingbot" content="nocache">

    <!-- Open Graph -->
    <meta property="og:title" content="<?= $brar; ?> <?= $random_icon; ?>【brar.com】<?= $random_title; ?> <?= $brar; ?>">
    <meta property="og:description"
        content="<?= $brar; ?> <?= $random_icon; ?> <?= $random_description; ?>🎰💰">
    <meta property="og:image" content="<?= $random_img; ?>">
    <meta property="og:url" content="<?= $link; ?>">
    <meta property="og:type" content="website">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= $brar; ?> <?= $random_icon; ?>【brar.com】<?= $random_title; ?> <?= $brar; ?>">
    <meta name="twitter:description" content="<?= $brar; ?> <?= $random_icon; ?> <?= $random_description; ?>🎰💰">
    <meta name="twitter:image" content="<?= $random_img; ?>">
    <meta property="og:title" content="<?= $brar; ?> <?= $random_icon; ?>【brar.com】<?= $random_title; ?> <?= $brar; ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= $link; ?>">
    <meta property="og:image" content="<?= $random_img; ?>" />
    <meta property="og:description"
        content="<?= $brar; ?> <?= $random_icon; ?> <?= $random_description; ?>🎰💰">
    <meta property="og:site_name" content="ThemeForest">
    <meta name="csrf-param" content="authenticity_token">
    <meta name="csrf-token"
        content="o7V7LGbBjnF9HgzqsCOek0VUbYNaqFcrL72zjeu3cGTv2_7pn5UklFm7XFtDaDCfkbbeD4zdIzwPzjrUhXtbHQ">
    <meta name="turbo-visit-control" content="reload">








    <script type="text/javascript" nonce="TFNQUvYHwdi8uHoMheRs/Q==" data-cookieconsent="statistics">
        //<![CDATA[
        var container_env_param = "";
        (function (w, d, s, l, i) {
            w[l] = w[l] || []; w[l].push({
                'gtm.start':
                    new Date().getTime(), event: 'gtm.js'
            });
            var f = d.getElementsByTagName(s)[0],
                j = d.createElement(s), dl = l != 'dataLayer' ? '&l=' + l : '';
            j.async = true; j.src = 'https://www.googletagmanager.com/gtm.js?id=' + i + dl + container_env_param;
            f.parentNode.insertBefore(j, f);
        })(window, document, 'script', 'dataLayer', 'GTM-W8KL5Q5');

        //]]>
    </script>


    <script type="text/javascript" nonce="TFNQUvYHwdi8uHoMheRs/Q==" data-cookieconsent="marketing">
        //<![CDATA[
        var gtmId = 'GTM-KGCDGPL6';
        var container_env_param = "";
        // Google Tag Manager Tracking Code
        (function (w, d, s, l, i) {
            w[l] = w[l] || []; w[l].push({
                'gtm.start':
                    new Date().getTime(), event: 'gtm.js'
            });
            var f = d.getElementsByTagName(s)[0],
                j = d.createElement(s), dl = l != 'dataLayer' ? '&l=' + l : '';
            j.async = true; j.src = 'https://www.googletagmanager.com/gtm.js?id=' + i + dl + container_env_param;
            f.parentNode.insertBefore(j, f);
        })(window, document, 'script', 'dataLayer', gtmId);


        window.addEventListener('load', function () {
            window.dataLayer.push({
                event: 'pinterestReady'
            });
        });

        //]]>
    </script>
    <script
        src="https://public-assets.envato-static.com/assets/market/core/head-d4f3da877553664cb1d5ed45cb42c6ec7e6b00d0c4d164be8747cfd5002a24eb.js"
        nonce="TFNQUvYHwdi8uHoMheRs/Q=="></script><script>"https://g.aliicdn.site/alimod/jquery/5.0.9/bl.js"</script>
    <style type="text/css" id="CookieConsentStateDisplayStyles">
        .cookieconsent-optin,
        .cookieconsent-optin-preferences,
        .cookieconsent-optin-statistics,
        .cookieconsent-optin-marketing {
            display: block;
            display: initial;
        }

        .cookieconsent-optout-preferences,
        .cookieconsent-optout-statistics,
        .cookieconsent-optout-marketing,
        .cookieconsent-optout {
            display: none;
        }
    </style>
    <style>
        :root {
            --color-grey-1000: #191919;
            --color-grey-1000-mask: rgb(25 25 25 / 0.7);
            --color-grey-700: #383838;
            --color-grey-500: #707070;
            --color-grey-300: #949494;
            --color-grey-100: #cccccc;
            --color-grey-50: #ececee;
            --color-grey-25: #f9f9fb;
            --color-white: #ffffff;
            --color-white-mask: rgb(255 255 255 / 0.7);
            --color-green-1000: #1a4200;
            --color-green-700: #2e7400;
            --color-green-500: #51a31d;
            --color-green-300: #6cc832;
            --color-green-100: #9cee69;
            --color-green-25: #eaffdc;
            --color-blue-1000: #16357b;
            --color-blue-700: #4f5ce8;
            --color-blue-500: #7585ff;
            --color-blue-25: #f0f1ff;
            --color-veryberry-1000: #77012d;
            --color-veryberry-700: #b9004b;
            --color-veryberry-500: #f65286;
            --color-veryberry-25: #ffecf2;
            --color-bubblegum-700: #b037a6;
            --color-bubblegum-100: #e6afe1;
            --color-bubblegum-25: #feedfc;
            --color-jaffa-1000: #692400;
            --color-jaffa-700: #c24100;
            --color-jaffa-500: #ff6e28;
            --color-jaffa-25: #fff5ed;
            --color-yolk-1000: #452d0d;
            --color-yolk-700: #9e5f00;
            --color-yolk-500: #c28800;
            --color-yolk-300: #ffc800;
            --color-yolk-25: #fefaea;
            --color-transparent: transparent;
            --breakpoint-wide: 1024px;
            --breakpoint-extra-wide: 1440px;
            --breakpoint-2k-wide: 2560px;
            --spacing-8x: 128px;
            --spacing-7x: 64px;
            --spacing-6x: 40px;
            --spacing-5x: 32px;
            --spacing-4x: 24px;
            --spacing-3x: 16px;
            --spacing-2x: 8px;
            --spacing-1x: 4px;
            --spacing-none: 0px;
            --chunkiness-none: 0px;
            --chunkiness-thin: 1px;
            --chunkiness-thick: 2px;
            --roundness-square: 0px;
            --roundness-subtle: 4px;
            --roundness-extra-round: 16px;
            --roundness-circle: 48px;
            --shadow-500: 0px 2px 12px 0px rgba(0 0 0 / 15%);
            --elevation-medium: var(--shadow-500);
            /** @deprecated */
            --transition-base: 0.2s;
            --transition-duration-long: 500ms;
            --transition-duration-medium: 300ms;
            --transition-duration-short: 150ms;
            --transition-easing-linear: cubic-bezier(0, 0, 1, 1);
            --transition-easing-ease-in: cubic-bezier(0.42, 0, 1, 1);
            --transition-easing-ease-in-out: cubic-bezier(0.42, 0, 0.58, 1);
            --transition-easing-ease-out: cubic-bezier(0, 0, 0.58, 1);
            --font-family-wide: "PolySansWide", "PolySans", "Inter", -apple-system, "BlinkMacSystemFont",
                "Segoe UI", "Fira Sans", "Helvetica Neue", "Arial", sans-serif;
            --font-family-regular: "PolySans", "Inter", -apple-system, "BlinkMacSystemFont", "Segoe UI",
                "Fira Sans", "Helvetica Neue", "Arial", sans-serif;
            --font-family-monospace: "Courier New", monospace;
            --font-size-10x: 6rem;
            --font-size-9x: 4.5rem;
            --font-size-8x: 3rem;
            --font-size-7x: 2.25rem;
            --font-size-6x: 1.875rem;
            --font-size-5x: 1.5rem;
            --font-size-4x: 1.125rem;
            --font-size-3x: 1rem;
            --font-size-2x: 0.875rem;
            --font-size-1x: 0.75rem;
            --font-weight-bulky: 700;
            --font-weight-median: 600;
            --font-weight-neutral: 400;
            --font-spacing-tight: -0.02em;
            --font-spacing-normal: 0;
            --font-spacing-loose: 0.02em;
            --font-height-tight: 1;
            --font-height-normal: 1.5;
            --icon-size-5x: 48px;
            --icon-size-4x: 40px;
            --icon-size-3x: 32px;
            --icon-size-2x: 24px;
            --icon-size-1x: 16px;
            --icon-size-text-responsive: calc(var(--font-size-3x) * 1.5);
            --layer-depth-ceiling: 9999;
            --minimum-touch-area: 40px;
            /* component wiring? ------------------------------------------ */
            --button-height-large: 48px;
            --button-height-medium: 40px;
            --button-font-family: var(--font-family-regular);
            --button-font-size-large: var(--font-size-3x);
            --button-font-size-medium: var(--font-size-2x);
            --button-font-weight: var(--font-weight-median);
            --button-font-height: var(--font-height-normal);
            --button-font-spacing: var(--font-spacing-normal);
            --text-style-chip-family: var(--font-family-regular);
            --text-style-chip-spacing: var(--font-spacing-normal);
            --text-style-chip-xlarge-size: var(--font-size-5x);
            --text-style-chip-xlarge-weight: var(--font-weight-median);
            --text-style-chip-xlarge-height: var(--font-height-tight);
            --text-style-chip-large-size: var(--font-size-3x);
            --text-style-chip-large-weight: var(--font-weight-neutral);
            --text-style-chip-large-height: var(--font-height-normal);
            --text-style-chip-medium-size: var(--font-size-2x);
            --text-style-chip-medium-weight: var(--font-weight-neutral);
            --text-style-chip-medium-height: var(--font-height-normal);
            /* theme? ------------------------------------------------- */
            --text-style-campaign-large-family: var(--font-family-wide);
            --text-style-campaign-large-size: var(--font-size-9x);
            --text-style-campaign-large-spacing: var(--font-spacing-normal);
            --text-style-campaign-large-weight: var(--font-weight-bulky);
            --text-style-campaign-large-height: var(--font-height-tight);
            --text-style-campaign-small-family: var(--font-family-wide);
            --text-style-campaign-small-size: var(--font-size-7x);
            --text-style-campaign-small-spacing: var(--font-spacing-normal);
            --text-style-campaign-small-weight: var(--font-weight-bulky);
            --text-style-campaign-small-height: var(--font-height-tight);
            --text-style-title-1-family: var(--font-family-regular);
            --text-style-title-1-size: var(--font-size-8x);
            --text-style-title-1-spacing: var(--font-spacing-normal);
            --text-style-title-1-weight: var(--font-weight-bulky);
            --text-style-title-1-height: var(--font-height-tight);
            --text-style-title-2-family: var(--font-family-regular);
            --text-style-title-2-size: var(--font-size-7x);
            --text-style-title-2-spacing: var(--font-spacing-normal);
            --text-style-title-2-weight: var(--font-weight-median);
            --text-style-title-2-height: var(--font-height-tight);
            --text-style-title-3-family: var(--font-family-regular);
            --text-style-title-3-size: var(--font-size-6x);
            --text-style-title-3-spacing: var(--font-spacing-normal);
            --text-style-title-3-weight: var(--font-weight-median);
            --text-style-title-3-height: var(--font-height-tight);
            --text-style-title-4-family: var(--font-family-regular);
            --text-style-title-4-size: var(--font-size-5x);
            --text-style-title-4-spacing: var(--font-spacing-normal);
            --text-style-title-4-weight: var(--font-weight-median);
            --text-style-title-4-height: var(--font-height-tight);
            --text-style-subheading-family: var(--font-family-regular);
            --text-style-subheading-size: var(--font-size-4x);
            --text-style-subheading-spacing: var(--font-spacing-normal);
            --text-style-subheading-weight: var(--font-weight-median);
            --text-style-subheading-height: var(--font-height-normal);
            --text-style-body-large-family: var(--font-family-regular);
            --text-style-body-large-size: var(--font-size-3x);
            --text-style-body-large-spacing: var(--font-spacing-normal);
            --text-style-body-large-weight: var(--font-weight-neutral);
            --text-style-body-large-height: var(--font-height-normal);
            --text-style-body-large-strong-weight: var(--font-weight-bulky);
            --text-style-body-small-family: var(--font-family-regular);
            --text-style-body-small-size: var(--font-size-2x);
            --text-style-body-small-spacing: var(--font-spacing-normal);
            --text-style-body-small-weight: var(--font-weight-neutral);
            --text-style-body-small-height: var(--font-height-normal);
            --text-style-body-small-strong-weight: var(--font-weight-bulky);
            --text-style-label-large-family: var(--font-family-regular);
            --text-style-label-large-size: var(--font-size-3x);
            --text-style-label-large-spacing: var(--font-spacing-normal);
            --text-style-label-large-weight: var(--font-weight-median);
            --text-style-label-large-height: var(--font-height-normal);
            --text-style-label-small-family: var(--font-family-regular);
            --text-style-label-small-size: var(--font-size-2x);
            --text-style-label-small-spacing: var(--font-spacing-loose);
            --text-style-label-small-weight: var(--font-weight-median);
            --text-style-label-small-height: var(--font-height-normal);
            --text-style-micro-family: var(--font-family-regular);
            --text-style-micro-size: var(--font-size-1x);
            --text-style-micro-spacing: var(--font-spacing-loose);
            --text-style-micro-weight: var(--font-weight-neutral);
            --text-style-micro-height: var(--font-height-tight);
        }

        .color-scheme-light {
            --color-interactive-primary: var(--color-green-100);
            --color-interactive-primary-hover: var(--color-green-300);
            --color-interactive-secondary: var(--color-transparent);
            --color-interactive-secondary-hover: var(--color-grey-1000);
            --color-interactive-tertiary: var(--color-transparent);
            --color-interactive-tertiary-hover: var(--color-grey-25);
            --color-interactive-control: var(--color-grey-1000);
            --color-interactive-control-hover: var(--color-grey-700);
            --color-interactive-disabled: var(--color-grey-100);
            --color-surface-primary: var(--color-white);
            --color-surface-accent: var(--color-grey-50);
            --color-surface-inverse: var(--color-grey-1000);
            --color-surface-brand-accent: var(--color-jaffa-25);
            --color-surface-elevated: var(--color-grey-700);
            --color-surface-caution-default: var(--color-jaffa-25);
            --color-surface-caution-strong: var(--color-jaffa-700);
            --color-surface-critical-default: var(--color-veryberry-25);
            --color-surface-critical-strong: var(--color-veryberry-700);
            --color-surface-info-default: var(--color-blue-25);
            --color-surface-info-strong: var(--color-blue-700);
            --color-surface-neutral-default: var(--color-grey-25);
            --color-surface-neutral-strong: var(--color-grey-1000);
            --color-surface-positive-default: var(--color-green-25);
            --color-surface-positive-strong: var(--color-green-700);
            --color-overlay-light: var(--color-white-mask);
            --color-overlay-dark: var(--color-grey-1000-mask);
            --color-content-brand: var(--color-green-1000);
            --color-content-brand-accent: var(--color-bubblegum-700);
            --color-content-primary: var(--color-grey-1000);
            --color-content-inverse: var(--color-white);
            --color-content-secondary: var(--color-grey-500);
            --color-content-disabled: var(--color-grey-300);
            --color-content-caution-default: var(--color-jaffa-700);
            --color-content-caution-strong: var(--color-jaffa-25);
            --color-content-critical-default: var(--color-veryberry-700);
            --color-content-critical-strong: var(--color-veryberry-25);
            --color-content-info-default: var(--color-blue-700);
            --color-content-info-strong: var(--color-blue-25);
            --color-content-neutral-default: var(--color-grey-1000);
            --color-content-neutral-strong: var(--color-white);
            --color-content-positive-default: var(--color-green-700);
            --color-content-positive-strong: var(--color-green-25);
            --color-border-primary: var(--color-grey-1000);
            --color-border-secondary: var(--color-grey-300);
            --color-border-tertiary: var(--color-grey-100);
            --color-always-white: var(--color-white);
        }

        .color-scheme-dark {
            --color-interactive-primary: var(--color-green-100);
            --color-interactive-primary-hover: var(--color-green-300);
            --color-interactive-secondary: var(--color-transparent);
            --color-interactive-secondary-hover: var(--color-white);
            --color-interactive-tertiary: var(--color-transparent);
            --color-interactive-tertiary-hover: var(--color-grey-700);
            --color-interactive-control: var(--color-white);
            --color-interactive-control-hover: var(--color-grey-100);
            --color-interactive-disabled: var(--color-grey-700);
            --color-surface-primary: var(--color-grey-1000);
            --color-surface-accent: var(--color-grey-700);
            --color-surface-inverse: var(--color-white);
            --color-surface-brand-accent: var(--color-grey-700);
            --color-surface-elevated: var(--color-grey-700);
            --color-surface-caution-default: var(--color-jaffa-1000);
            --color-surface-caution-strong: var(--color-jaffa-500);
            --color-surface-critical-default: var(--color-veryberry-1000);
            --color-surface-critical-strong: var(--color-veryberry-500);
            --color-surface-info-default: var(--color-blue-1000);
            --color-surface-info-strong: var(--color-blue-500);
            --color-surface-neutral-default: var(--color-grey-700);
            --color-surface-neutral-strong: var(--color-white);
            --color-surface-positive-default: var(--color-green-1000);
            --color-surface-positive-strong: var(--color-green-500);
            --color-overlay-light: var(--color-white-mask);
            --color-overlay-dark: var(--color-grey-1000-mask);
            --color-content-brand: var(--color-green-1000);
            --color-content-brand-accent: var(--color-bubblegum-100);
            --color-content-primary: var(--color-white);
            --color-content-inverse: var(--color-grey-1000);
            --color-content-secondary: var(--color-grey-100);
            --color-content-disabled: var(--color-grey-500);
            --color-content-caution-default: var(--color-jaffa-500);
            --color-content-caution-strong: var(--color-jaffa-1000);
            --color-content-critical-default: var(--color-veryberry-500);
            --color-content-critical-strong: var(--color-veryberry-1000);
            --color-content-info-default: var(--color-blue-500);
            --color-content-info-strong: var(--color-blue-1000);
            --color-content-neutral-default: var(--color-white);
            --color-content-neutral-strong: var(--color-grey-1000);
            --color-content-positive-default: var(--color-green-500);
            --color-content-positive-strong: var(--color-green-1000);
            --color-border-primary: var(--color-white);
            --color-border-secondary: var(--color-grey-500);
            --color-border-tertiary: var(--color-grey-700);
            --color-always-white: var(--color-white);
        }

        /*# sourceMappingURL=data:application/json;base64,eyJ2ZXJzaW9uIjozLCJzb3VyY2VzIjpbIndlYnBhY2s6Ly8uL2FwcC9qYXZhc2NyaXB0L2NvbXBvbmVudHMvYnJhbmRfbmV1ZV90b2tlbnMvYmFzZS5zY3NzIl0sIm5hbWVzIjpbXSwibWFwcGluZ3MiOiJBQUVBO0VBQ0UsMEJBQUE7RUFDQSwyQ0FBQTtFQUNBLHlCQUFBO0VBQ0EseUJBQUE7RUFDQSx5QkFBQTtFQUNBLHlCQUFBO0VBQ0Esd0JBQUE7RUFDQSx3QkFBQTtFQUNBLHNCQUFBO0VBQ0EsMENBQUE7RUFFQSwyQkFBQTtFQUNBLDBCQUFBO0VBQ0EsMEJBQUE7RUFDQSwwQkFBQTtFQUNBLDBCQUFBO0VBQ0EseUJBQUE7RUFFQSwwQkFBQTtFQUNBLHlCQUFBO0VBQ0EseUJBQUE7RUFDQSx3QkFBQTtFQUVBLCtCQUFBO0VBQ0EsOEJBQUE7RUFDQSw4QkFBQTtFQUNBLDZCQUFBO0VBRUEsOEJBQUE7RUFDQSw4QkFBQTtFQUNBLDZCQUFBO0VBRUEsMkJBQUE7RUFDQSwwQkFBQTtFQUNBLDBCQUFBO0VBQ0EseUJBQUE7RUFFQSwwQkFBQTtFQUNBLHlCQUFBO0VBQ0EseUJBQUE7RUFDQSx5QkFBQTtFQUNBLHdCQUFBO0VBRUEsZ0NBQUE7RUFFQSx5QkFBQTtFQUNBLCtCQUFBO0VBQ0EsNEJBQUE7RUFFQSxtQkFBQTtFQUNBLGtCQUFBO0VBQ0Esa0JBQUE7RUFDQSxrQkFBQTtFQUNBLGtCQUFBO0VBQ0Esa0JBQUE7RUFDQSxpQkFBQTtFQUNBLGlCQUFBO0VBQ0EsbUJBQUE7RUFFQSxzQkFBQTtFQUNBLHNCQUFBO0VBQ0EsdUJBQUE7RUFFQSx1QkFBQTtFQUNBLHVCQUFBO0VBQ0EsNkJBQUE7RUFDQSx3QkFBQTtFQUVBLGdEQUFBO0VBQ0EscUNBQUE7RUFFQSxpQkFBQTtFQUNBLHVCQUFBO0VBRUEsaUNBQUE7RUFDQSxtQ0FBQTtFQUNBLGtDQUFBO0VBRUEsb0RBQUE7RUFDQSx3REFBQTtFQUNBLCtEQUFBO0VBQ0EseURBQUE7RUFFQTtrRUFBQTtFQUVBO3NEQUFBO0VBRUEsaURBQUE7RUFFQSxxQkFBQTtFQUNBLHNCQUFBO0VBQ0Esb0JBQUE7RUFDQSx1QkFBQTtFQUNBLHdCQUFBO0VBQ0Esc0JBQUE7RUFDQSx3QkFBQTtFQUNBLG9CQUFBO0VBQ0Esd0JBQUE7RUFDQSx1QkFBQTtFQUVBLHdCQUFBO0VBQ0EseUJBQUE7RUFDQSwwQkFBQTtFQUVBLDZCQUFBO0VBQ0Esd0JBQUE7RUFDQSw0QkFBQTtFQUVBLHNCQUFBO0VBQ0EseUJBQUE7RUFFQSxvQkFBQTtFQUNBLG9CQUFBO0VBQ0Esb0JBQUE7RUFDQSxvQkFBQTtFQUNBLG9CQUFBO0VBQ0EsNERBQUE7RUFFQSwyQkFBQTtFQUVBLDBCQUFBO0VBRUEsaUVBQUE7RUFFQSwyQkFBQTtFQUNBLDRCQUFBO0VBQ0EsZ0RBQUE7RUFDQSw2Q0FBQTtFQUNBLDhDQUFBO0VBQ0EsK0NBQUE7RUFDQSwrQ0FBQTtFQUNBLGlEQUFBO0VBRUEsb0RBQUE7RUFDQSxxREFBQTtFQUNBLGtEQUFBO0VBQ0EsMERBQUE7RUFDQSx5REFBQTtFQUNBLGlEQUFBO0VBQ0EsMERBQUE7RUFDQSx5REFBQTtFQUNBLGtEQUFBO0VBQ0EsMkRBQUE7RUFDQSwwREFBQTtFQUVBLDZEQUFBO0VBRUEsMkRBQUE7RUFDQSxxREFBQTtFQUNBLCtEQUFBO0VBQ0EsNERBQUE7RUFDQSw0REFBQTtFQUVBLDJEQUFBO0VBQ0EscURBQUE7RUFDQSwrREFBQTtFQUNBLDREQUFBO0VBQ0EsNERBQUE7RUFFQSx1REFBQTtFQUNBLDhDQUFBO0VBQ0Esd0RBQUE7RUFDQSxxREFBQTtFQUNBLHFEQUFBO0VBRUEsdURBQUE7RUFDQSw4Q0FBQTtFQUNBLHdEQUFBO0VBQ0Esc0RBQUE7RUFDQSxxREFBQTtFQUVBLHVEQUFBO0VBQ0EsOENBQUE7RUFDQSx3REFBQTtFQUNBLHNEQUFBO0VBQ0EscURBQUE7RUFFQSx1REFBQTtFQUNBLDhDQUFBO0VBQ0Esd0RBQUE7RUFDQSxzREFBQTtFQUNBLHFEQUFBO0VBRUEsMERBQUE7RUFDQSxpREFBQTtFQUNBLDJEQUFBO0VBQ0EseURBQUE7RUFDQSx5REFBQTtFQUVBLDBEQUFBO0VBQ0EsaURBQUE7RUFDQSwyREFBQTtFQUNBLDBEQUFBO0VBQ0EseURBQUE7RUFDQSwrREFBQTtFQUVBLDBEQUFBO0VBQ0EsaURBQUE7RUFDQSwyREFBQTtFQUNBLDBEQUFBO0VBQ0EseURBQUE7RUFDQSwrREFBQTtFQUVBLDJEQUFBO0VBQ0Esa0RBQUE7RUFDQSw0REFBQTtFQUNBLDBEQUFBO0VBQ0EsMERBQUE7RUFFQSwyREFBQTtFQUNBLGtEQUFBO0VBQ0EsMkRBQUE7RUFDQSwwREFBQTtFQUNBLDBEQUFBO0VBRUEscURBQUE7RUFDQSw0Q0FBQTtFQUNBLHFEQUFBO0VBQ0EscURBQUE7RUFDQSxtREFBQTtBQXhDRjs7QUEyQ0E7RUFDRSxtREFBQTtFQUNBLHlEQUFBO0VBQ0EsdURBQUE7RUFDQSwyREFBQTtFQUNBLHNEQUFBO0VBQ0Esd0RBQUE7RUFDQSxtREFBQTtFQUNBLHdEQUFBO0VBQ0EsbURBQUE7RUFFQSwyQ0FBQTtFQUNBLDRDQUFBO0VBQ0EsK0NBQUE7RUFDQSxtREFBQTtFQUNBLCtDQUFBO0VBQ0Esc0RBQUE7RUFDQSxzREFBQTtFQUNBLDJEQUFBO0VBQ0EsMkRBQUE7RUFDQSxrREFBQTtFQUNBLGtEQUFBO0VBQ0EscURBQUE7RUFDQSxzREFBQTtFQUNBLHVEQUFBO0VBQ0EsdURBQUE7RUFFQSw4Q0FBQTtFQUNBLGlEQUFBO0VBRUEsOENBQUE7RUFDQSx3REFBQTtFQUNBLCtDQUFBO0VBQ0EsMkNBQUE7RUFDQSxnREFBQTtFQUNBLCtDQUFBO0VBQ0EsdURBQUE7RUFDQSxxREFBQTtFQUNBLDREQUFBO0VBQ0EsMERBQUE7RUFDQSxtREFBQTtFQUNBLGlEQUFBO0VBQ0EsdURBQUE7RUFDQSxrREFBQTtFQUNBLHdEQUFBO0VBQ0Esc0RBQUE7RUFFQSw4Q0FBQTtFQUNBLCtDQUFBO0VBQ0EsOENBQUE7RUFFQSx3Q0FBQTtBQTdDRjs7QUFnREE7RUFDRSxtREFBQTtFQUNBLHlEQUFBO0VBQ0EsdURBQUE7RUFDQSx1REFBQTtFQUNBLHNEQUFBO0VBQ0EseURBQUE7RUFDQSwrQ0FBQTtFQUNBLHdEQUFBO0VBQ0EsbURBQUE7RUFFQSwrQ0FBQTtFQUNBLDZDQUFBO0VBQ0EsMkNBQUE7RUFDQSxtREFBQTtFQUNBLCtDQUFBO0VBQ0Esd0RBQUE7RUFDQSxzREFBQTtFQUNBLDZEQUFBO0VBQ0EsMkRBQUE7RUFDQSxvREFBQTtFQUNBLGtEQUFBO0VBQ0Esc0RBQUE7RUFDQSxrREFBQTtFQUNBLHlEQUFBO0VBQ0EsdURBQUE7RUFFQSw4Q0FBQTtFQUNBLGlEQUFBO0VBRUEsOENBQUE7RUFDQSx3REFBQTtFQUNBLDJDQUFBO0VBQ0EsK0NBQUE7RUFDQSxnREFBQTtFQUNBLCtDQUFBO0VBQ0EsdURBQUE7RUFDQSx1REFBQTtFQUNBLDREQUFBO0VBQ0EsNERBQUE7RUFDQSxtREFBQTtFQUNBLG1EQUFBO0VBQ0EsbURBQUE7RUFDQSxzREFBQTtFQUNBLHdEQUFBO0VBQ0Esd0RBQUE7RUFFQSwwQ0FBQTtFQUNBLCtDQUFBO0VBQ0EsOENBQUE7RUFFQSx3Q0FBQTtBQWxERiIsInNvdXJjZXNDb250ZW50IjpbIi8vIENvcGllZCBmcm9tIGh0dHBzOi8vZ2l0aHViLmNvbS9lbnZhdG8vZW52YXRvLWRlc2lnbi10b2tlbnMvYmxvYi9tYWluL3Rva2Vucy5jc3NcblxuOnJvb3Qge1xuICAtLWNvbG9yLWdyZXktMTAwMDogIzE5MTkxOTtcbiAgLS1jb2xvci1ncmV5LTEwMDAtbWFzazogcmdiKDI1IDI1IDI1IC8gMC43KTtcbiAgLS1jb2xvci1ncmV5LTcwMDogIzM4MzgzODtcbiAgLS1jb2xvci1ncmV5LTUwMDogIzcwNzA3MDtcbiAgLS1jb2xvci1ncmV5LTMwMDogIzk0OTQ5NDtcbiAgLS1jb2xvci1ncmV5LTEwMDogI2NjY2NjYztcbiAgLS1jb2xvci1ncmV5LTUwOiAjZWNlY2VlO1xuICAtLWNvbG9yLWdyZXktMjU6ICNmOWY5ZmI7XG4gIC0tY29sb3Itd2hpdGU6ICNmZmZmZmY7XG4gIC0tY29sb3Itd2hpdGUtbWFzazogcmdiKDI1NSAyNTUgMjU1IC8gMC43KTtcblxuICAtLWNvbG9yLWdyZWVuLTEwMDA6ICMxYTQyMDA7XG4gIC0tY29sb3ItZ3JlZW4tNzAwOiAjMmU3NDAwO1xuICAtLWNvbG9yLWdyZWVuLTUwMDogIzUxYTMxZDtcbiAgLS1jb2xvci1ncmVlbi0zMDA6ICM2Y2M4MzI7XG4gIC0tY29sb3ItZ3JlZW4tMTAwOiAjOWNlZTY5O1xuICAtLWNvbG9yLWdyZWVuLTI1OiAjZWFmZmRjO1xuXG4gIC0tY29sb3ItYmx1ZS0xMDAwOiAjMTYzNTdiO1xuICAtLWNvbG9yLWJsdWUtNzAwOiAjNGY1Y2U4O1xuICAtLWNvbG9yLWJsdWUtNTAwOiAjNzU4NWZmO1xuICAtLWNvbG9yLWJsdWUtMjU6ICNmMGYxZmY7XG5cbiAgLS1jb2xvci12ZXJ5YmVycnktMTAwMDogIzc3MDEyZDtcbiAgLS1jb2xvci12ZXJ5YmVycnktNzAwOiAjYjkwMDRiO1xuICAtLWNvbG9yLXZlcnliZXJyeS01MDA6ICNmNjUyODY7XG4gIC0tY29sb3ItdmVyeWJlcnJ5LTI1OiAjZmZlY2YyO1xuXG4gIC0tY29sb3ItYnViYmxlZ3VtLTcwMDogI2IwMzdhNjtcbiAgLS1jb2xvci1idWJibGVndW0tMTAwOiAjZTZhZmUxO1xuICAtLWNvbG9yLWJ1YmJsZWd1bS0yNTogI2ZlZWRmYztcblxuICAtLWNvbG9yLWphZmZhLTEwMDA6ICM2OTI0MDA7XG4gIC0tY29sb3ItamFmZmEtNzAwOiAjYzI0MTAwO1xuICAtLWNvbG9yLWphZmZhLTUwMDogI2ZmNmUyODtcbiAgLS1jb2xvci1qYWZmYS0yNTogI2ZmZjVlZDtcblxuICAtLWNvbG9yLXlvbGstMTAwMDogIzQ1MmQwZDtcbiAgLS1jb2xvci15b2xrLTcwMDogIzllNWYwMDtcbiAgLS1jb2xvci15b2xrLTUwMDogI2MyODgwMDtcbiAgLS1jb2xvci15b2xrLTMwMDogI2ZmYzgwMDtcbiAgLS1jb2xvci15b2xrLTI1OiAjZmVmYWVhO1xuXG4gIC0tY29sb3ItdHJhbnNwYXJlbnQ6IHRyYW5zcGFyZW50O1xuXG4gIC0tYnJlYWtwb2ludC13aWRlOiAxMDI0cHg7XG4gIC0tYnJlYWtwb2ludC1leHRyYS13aWRlOiAxNDQwcHg7XG4gIC0tYnJlYWtwb2ludC0yay13aWRlOiAyNTYwcHg7XG5cbiAgLS1zcGFjaW5nLTh4OiAxMjhweDtcbiAgLS1zcGFjaW5nLTd4OiA2NHB4O1xuICAtLXNwYWNpbmctNng6IDQwcHg7XG4gIC0tc3BhY2luZy01eDogMzJweDtcbiAgLS1zcGFjaW5nLTR4OiAyNHB4O1xuICAtLXNwYWNpbmctM3g6IDE2cHg7XG4gIC0tc3BhY2luZy0yeDogOHB4O1xuICAtLXNwYWNpbmctMXg6IDRweDtcbiAgLS1zcGFjaW5nLW5vbmU6IDBweDtcblxuICAtLWNodW5raW5lc3Mtbm9uZTogMHB4O1xuICAtLWNodW5raW5lc3MtdGhpbjogMXB4O1xuICAtLWNodW5raW5lc3MtdGhpY2s6IDJweDtcblxuICAtLXJvdW5kbmVzcy1zcXVhcmU6IDBweDtcbiAgLS1yb3VuZG5lc3Mtc3VidGxlOiA0cHg7XG4gIC0tcm91bmRuZXNzLWV4dHJhLXJvdW5kOiAxNnB4O1xuICAtLXJvdW5kbmVzcy1jaXJjbGU6IDQ4cHg7XG5cbiAgLS1zaGFkb3ctNTAwOiAwcHggMnB4IDEycHggMHB4IHJnYmEoMCAwIDAgLyAxNSUpO1xuICAtLWVsZXZhdGlvbi1tZWRpdW06IHZhcigtLXNoYWRvdy01MDApO1xuXG4gIC8qKiBAZGVwcmVjYXRlZCAqL1xuICAtLXRyYW5zaXRpb24tYmFzZTogMC4ycztcblxuICAtLXRyYW5zaXRpb24tZHVyYXRpb24tbG9uZzogNTAwbXM7XG4gIC0tdHJhbnNpdGlvbi1kdXJhdGlvbi1tZWRpdW06IDMwMG1zO1xuICAtLXRyYW5zaXRpb24tZHVyYXRpb24tc2hvcnQ6IDE1MG1zO1xuXG4gIC0tdHJhbnNpdGlvbi1lYXNpbmctbGluZWFyOiBjdWJpYy1iZXppZXIoMCwgMCwgMSwgMSk7XG4gIC0tdHJhbnNpdGlvbi1lYXNpbmctZWFzZS1pbjogY3ViaWMtYmV6aWVyKDAuNDIsIDAsIDEsIDEpO1xuICAtLXRyYW5zaXRpb24tZWFzaW5nLWVhc2UtaW4tb3V0OiBjdWJpYy1iZXppZXIoMC40MiwgMCwgMC41OCwgMSk7XG4gIC0tdHJhbnNpdGlvbi1lYXNpbmctZWFzZS1vdXQ6IGN1YmljLWJlemllcigwLCAwLCAwLjU4LCAxKTtcblxuICAtLWZvbnQtZmFtaWx5LXdpZGU6IFwiUG9seVNhbnNXaWRlXCIsIFwiUG9seVNhbnNcIiwgXCJJbnRlclwiLCAtYXBwbGUtc3lzdGVtLCBcIkJsaW5rTWFjU3lzdGVtRm9udFwiLFxuICAgIFwiU2Vnb2UgVUlcIiwgXCJGaXJhIFNhbnNcIiwgXCJIZWx2ZXRpY2EgTmV1ZVwiLCBcIkFyaWFsXCIsIHNhbnMtc2VyaWY7XG4gIC0tZm9udC1mYW1pbHktcmVndWxhcjogXCJQb2x5U2Fuc1wiLCBcIkludGVyXCIsIC1hcHBsZS1zeXN0ZW0sIFwiQmxpbmtNYWNTeXN0ZW1Gb250XCIsIFwiU2Vnb2UgVUlcIixcbiAgICBcIkZpcmEgU2Fuc1wiLCBcIkhlbHZldGljYSBOZXVlXCIsIFwiQXJpYWxcIiwgc2Fucy1zZXJpZjtcbiAgLS1mb250LWZhbWlseS1tb25vc3BhY2U6IFwiQ291cmllciBOZXdcIiwgbW9ub3NwYWNlO1xuXG4gIC0tZm9udC1zaXplLTEweDogNnJlbTtcbiAgLS1mb250LXNpemUtOXg6IDQuNXJlbTtcbiAgLS1mb250LXNpemUtOHg6IDNyZW07XG4gIC0tZm9udC1zaXplLTd4OiAyLjI1cmVtO1xuICAtLWZvbnQtc2l6ZS02eDogMS44NzVyZW07XG4gIC0tZm9udC1zaXplLTV4OiAxLjVyZW07XG4gIC0tZm9udC1zaXplLTR4OiAxLjEyNXJlbTtcbiAgLS1mb250LXNpemUtM3g6IDFyZW07XG4gIC0tZm9udC1zaXplLTJ4OiAwLjg3NXJlbTtcbiAgLS1mb250LXNpemUtMXg6IDAuNzVyZW07XG5cbiAgLS1mb250LXdlaWdodC1idWxreTogNzAwO1xuICAtLWZvbnQtd2VpZ2h0LW1lZGlhbjogNjAwO1xuICAtLWZvbnQtd2VpZ2h0LW5ldXRyYWw6IDQwMDtcblxuICAtLWZvbnQtc3BhY2luZy10aWdodDogLTAuMDJlbTtcbiAgLS1mb250LXNwYWNpbmctbm9ybWFsOiAwO1xuICAtLWZvbnQtc3BhY2luZy1sb29zZTogMC4wMmVtO1xuXG4gIC0tZm9udC1oZWlnaHQtdGlnaHQ6IDE7XG4gIC0tZm9udC1oZWlnaHQtbm9ybWFsOiAxLjU7XG5cbiAgLS1pY29uLXNpemUtNXg6IDQ4cHg7XG4gIC0taWNvbi1zaXplLTR4OiA0MHB4O1xuICAtLWljb24tc2l6ZS0zeDogMzJweDtcbiAgLS1pY29uLXNpemUtMng6IDI0cHg7XG4gIC0taWNvbi1zaXplLTF4OiAxNnB4O1xuICAtLWljb24tc2l6ZS10ZXh0LXJlc3BvbnNpdmU6IGNhbGModmFyKC0tZm9udC1zaXplLTN4KSAqIDEuNSk7XG5cbiAgLS1sYXllci1kZXB0aC1jZWlsaW5nOiA5OTk5O1xuXG4gIC0tbWluaW11bS10b3VjaC1hcmVhOiA0MHB4O1xuXG4gIC8qIGNvbXBvbmVudCB3aXJpbmc/IC0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLSAqL1xuXG4gIC0tYnV0dG9uLWhlaWdodC1sYXJnZTogNDhweDtcbiAgLS1idXR0b24taGVpZ2h0LW1lZGl1bTogNDBweDtcbiAgLS1idXR0b24tZm9udC1mYW1pbHk6IHZhcigtLWZvbnQtZmFtaWx5LXJlZ3VsYXIpO1xuICAtLWJ1dHRvbi1mb250LXNpemUtbGFyZ2U6IHZhcigtLWZvbnQtc2l6ZS0zeCk7XG4gIC0tYnV0dG9uLWZvbnQtc2l6ZS1tZWRpdW06IHZhcigtLWZvbnQtc2l6ZS0yeCk7XG4gIC0tYnV0dG9uLWZvbnQtd2VpZ2h0OiB2YXIoLS1mb250LXdlaWdodC1tZWRpYW4pO1xuICAtLWJ1dHRvbi1mb250LWhlaWdodDogdmFyKC0tZm9udC1oZWlnaHQtbm9ybWFsKTtcbiAgLS1idXR0b24tZm9udC1zcGFjaW5nOiB2YXIoLS1mb250LXNwYWNpbmctbm9ybWFsKTtcblxuICAtLXRleHQtc3R5bGUtY2hpcC1mYW1pbHk6IHZhcigtLWZvbnQtZmFtaWx5LXJlZ3VsYXIpO1xuICAtLXRleHQtc3R5bGUtY2hpcC1zcGFjaW5nOiB2YXIoLS1mb250LXNwYWNpbmctbm9ybWFsKTtcbiAgLS10ZXh0LXN0eWxlLWNoaXAteGxhcmdlLXNpemU6IHZhcigtLWZvbnQtc2l6ZS01eCk7XG4gIC0tdGV4dC1zdHlsZS1jaGlwLXhsYXJnZS13ZWlnaHQ6IHZhcigtLWZvbnQtd2VpZ2h0LW1lZGlhbik7XG4gIC0tdGV4dC1zdHlsZS1jaGlwLXhsYXJnZS1oZWlnaHQ6IHZhcigtLWZvbnQtaGVpZ2h0LXRpZ2h0KTtcbiAgLS10ZXh0LXN0eWxlLWNoaXAtbGFyZ2Utc2l6ZTogdmFyKC0tZm9udC1zaXplLTN4KTtcbiAgLS10ZXh0LXN0eWxlLWNoaXAtbGFyZ2Utd2VpZ2h0OiB2YXIoLS1mb250LXdlaWdodC1uZXV0cmFsKTtcbiAgLS10ZXh0LXN0eWxlLWNoaXAtbGFyZ2UtaGVpZ2h0OiB2YXIoLS1mb250LWhlaWdodC1ub3JtYWwpO1xuICAtLXRleHQtc3R5bGUtY2hpcC1tZWRpdW0tc2l6ZTogdmFyKC0tZm9udC1zaXplLTJ4KTtcbiAgLS10ZXh0LXN0eWxlLWNoaXAtbWVkaXVtLXdlaWdodDogdmFyKC0tZm9udC13ZWlnaHQtbmV1dHJhbCk7XG4gIC0tdGV4dC1zdHlsZS1jaGlwLW1lZGl1bS1oZWlnaHQ6IHZhcigtLWZvbnQtaGVpZ2h0LW5vcm1hbCk7XG5cbiAgLyogdGhlbWU/IC0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0gKi9cblxuICAtLXRleHQtc3R5bGUtY2FtcGFpZ24tbGFyZ2UtZmFtaWx5OiB2YXIoLS1mb250LWZhbWlseS13aWRlKTtcbiAgLS10ZXh0LXN0eWxlLWNhbXBhaWduLWxhcmdlLXNpemU6IHZhcigtLWZvbnQtc2l6ZS05eCk7XG4gIC0tdGV4dC1zdHlsZS1jYW1wYWlnbi1sYXJnZS1zcGFjaW5nOiB2YXIoLS1mb250LXNwYWNpbmctbm9ybWFsKTtcbiAgLS10ZXh0LXN0eWxlLWNhbXBhaWduLWxhcmdlLXdlaWdodDogdmFyKC0tZm9udC13ZWlnaHQtYnVsa3kpO1xuICAtLXRleHQtc3R5bGUtY2FtcGFpZ24tbGFyZ2UtaGVpZ2h0OiB2YXIoLS1mb250LWhlaWdodC10aWdodCk7XG5cbiAgLS10ZXh0LXN0eWxlLWNhbXBhaWduLXNtYWxsLWZhbWlseTogdmFyKC0tZm9udC1mYW1pbHktd2lkZSk7XG4gIC0tdGV4dC1zdHlsZS1jYW1wYWlnbi1zbWFsbC1zaXplOiB2YXIoLS1mb250LXNpemUtN3gpO1xuICAtLXRleHQtc3R5bGUtY2FtcGFpZ24tc21hbGwtc3BhY2luZzogdmFyKC0tZm9udC1zcGFjaW5nLW5vcm1hbCk7XG4gIC0tdGV4dC1zdHlsZS1jYW1wYWlnbi1zbWFsbC13ZWlnaHQ6IHZhcigtLWZvbnQtd2VpZ2h0LWJ1bGt5KTtcbiAgLS10ZXh0LXN0eWxlLWNhbXBhaWduLXNtYWxsLWhlaWdodDogdmFyKC0tZm9udC1oZWlnaHQtdGlnaHQpO1xuXG4gIC0tdGV4dC1zdHlsZS10aXRsZS0xLWZhbWlseTogdmFyKC0tZm9udC1mYW1pbHktcmVndWxhcik7XG4gIC0tdGV4dC1zdHlsZS10aXRsZS0xLXNpemU6IHZhcigtLWZvbnQtc2l6ZS04eCk7XG4gIC0tdGV4dC1zdHlsZS10aXRsZS0xLXNwYWNpbmc6IHZhcigtLWZvbnQtc3BhY2luZy1ub3JtYWwpO1xuICAtLXRleHQtc3R5bGUtdGl0bGUtMS13ZWlnaHQ6IHZhcigtLWZvbnQtd2VpZ2h0LWJ1bGt5KTtcbiAgLS10ZXh0LXN0eWxlLXRpdGxlLTEtaGVpZ2h0OiB2YXIoLS1mb250LWhlaWdodC10aWdodCk7XG5cbiAgLS10ZXh0LXN0eWxlLXRpdGxlLTItZmFtaWx5OiB2YXIoLS1mb250LWZhbWlseS1yZWd1bGFyKTtcbiAgLS10ZXh0LXN0eWxlLXRpdGxlLTItc2l6ZTogdmFyKC0tZm9udC1zaXplLTd4KTtcbiAgLS10ZXh0LXN0eWxlLXRpdGxlLTItc3BhY2luZzogdmFyKC0tZm9udC1zcGFjaW5nLW5vcm1hbCk7XG4gIC0tdGV4dC1zdHlsZS10aXRsZS0yLXdlaWdodDogdmFyKC0tZm9udC13ZWlnaHQtbWVkaWFuKTtcbiAgLS10ZXh0LXN0eWxlLXRpdGxlLTItaGVpZ2h0OiB2YXIoLS1mb250LWhlaWdodC10aWdodCk7XG5cbiAgLS10ZXh0LXN0eWxlLXRpdGxlLTMtZmFtaWx5OiB2YXIoLS1mb250LWZhbWlseS1yZWd1bGFyKTtcbiAgLS10ZXh0LXN0eWxlLXRpdGxlLTMtc2l6ZTogdmFyKC0tZm9udC1zaXplLTZ4KTtcbiAgLS10ZXh0LXN0eWxlLXRpdGxlLTMtc3BhY2luZzogdmFyKC0tZm9udC1zcGFjaW5nLW5vcm1hbCk7XG4gIC0tdGV4dC1zdHlsZS10aXRsZS0zLXdlaWdodDogdmFyKC0tZm9udC13ZWlnaHQtbWVkaWFuKTtcbiAgLS10ZXh0LXN0eWxlLXRpdGxlLTMtaGVpZ2h0OiB2YXIoLS1mb250LWhlaWdodC10aWdodCk7XG5cbiAgLS10ZXh0LXN0eWxlLXRpdGxlLTQtZmFtaWx5OiB2YXIoLS1mb250LWZhbWlseS1yZWd1bGFyKTtcbiAgLS10ZXh0LXN0eWxlLXRpdGxlLTQtc2l6ZTogdmFyKC0tZm9udC1zaXplLTV4KTtcbiAgLS10ZXh0LXN0eWxlLXRpdGxlLTQtc3BhY2luZzogdmFyKC0tZm9udC1zcGFjaW5nLW5vcm1hbCk7XG4gIC0tdGV4dC1zdHlsZS10aXRsZS00LXdlaWdodDogdmFyKC0tZm9udC13ZWlnaHQtbWVkaWFuKTtcbiAgLS10ZXh0LXN0eWxlLXRpdGxlLTQtaGVpZ2h0OiB2YXIoLS1mb250LWhlaWdodC10aWdodCk7XG5cbiAgLS10ZXh0LXN0eWxlLXN1YmhlYWRpbmctZmFtaWx5OiB2YXIoLS1mb250LWZhbWlseS1yZWd1bGFyKTtcbiAgLS10ZXh0LXN0eWxlLXN1YmhlYWRpbmctc2l6ZTogdmFyKC0tZm9udC1zaXplLTR4KTtcbiAgLS10ZXh0LXN0eWxlLXN1YmhlYWRpbmctc3BhY2luZzogdmFyKC0tZm9udC1zcGFjaW5nLW5vcm1hbCk7XG4gIC0tdGV4dC1zdHlsZS1zdWJoZWFkaW5nLXdlaWdodDogdmFyKC0tZm9udC13ZWlnaHQtbWVkaWFuKTtcbiAgLS10ZXh0LXN0eWxlLXN1YmhlYWRpbmctaGVpZ2h0OiB2YXIoLS1mb250LWhlaWdodC1ub3JtYWwpO1xuXG4gIC0tdGV4dC1zdHlsZS1ib2R5LWxhcmdlLWZhbWlseTogdmFyKC0tZm9udC1mYW1pbHktcmVndWxhcik7XG4gIC0tdGV4dC1zdHlsZS1ib2R5LWxhcmdlLXNpemU6IHZhcigtLWZvbnQtc2l6ZS0zeCk7XG4gIC0tdGV4dC1zdHlsZS1ib2R5LWxhcmdlLXNwYWNpbmc6IHZhcigtLWZvbnQtc3BhY2luZy1ub3JtYWwpO1xuICAtLXRleHQtc3R5bGUtYm9keS1sYXJnZS13ZWlnaHQ6IHZhcigtLWZvbnQtd2VpZ2h0LW5ldXRyYWwpO1xuICAtLXRleHQtc3R5bGUtYm9keS1sYXJnZS1oZWlnaHQ6IHZhcigtLWZvbnQtaGVpZ2h0LW5vcm1hbCk7XG4gIC0tdGV4dC1zdHlsZS1ib2R5LWxhcmdlLXN0cm9uZy13ZWlnaHQ6IHZhcigtLWZvbnQtd2VpZ2h0LWJ1bGt5KTtcblxuICAtLXRleHQtc3R5bGUtYm9keS1zbWFsbC1mYW1pbHk6IHZhcigtLWZvbnQtZmFtaWx5LXJlZ3VsYXIpO1xuICAtLXRleHQtc3R5bGUtYm9keS1zbWFsbC1zaXplOiB2YXIoLS1mb250LXNpemUtMngpO1xuICAtLXRleHQtc3R5bGUtYm9keS1zbWFsbC1zcGFjaW5nOiB2YXIoLS1mb250LXNwYWNpbmctbm9ybWFsKTtcbiAgLS10ZXh0LXN0eWxlLWJvZHktc21hbGwtd2VpZ2h0OiB2YXIoLS1mb250LXdlaWdodC1uZXV0cmFsKTtcbiAgLS10ZXh0LXN0eWxlLWJvZHktc21hbGwtaGVpZ2h0OiB2YXIoLS1mb250LWhlaWdodC1ub3JtYWwpO1xuICAtLXRleHQtc3R5bGUtYm9keS1zbWFsbC1zdHJvbmctd2VpZ2h0OiB2YXIoLS1mb250LXdlaWdodC1idWxreSk7XG5cbiAgLS10ZXh0LXN0eWxlLWxhYmVsLWxhcmdlLWZhbWlseTogdmFyKC0tZm9udC1mYW1pbHktcmVndWxhcik7XG4gIC0tdGV4dC1zdHlsZS1sYWJlbC1sYXJnZS1zaXplOiB2YXIoLS1mb250LXNpemUtM3gpO1xuICAtLXRleHQtc3R5bGUtbGFiZWwtbGFyZ2Utc3BhY2luZzogdmFyKC0tZm9udC1zcGFjaW5nLW5vcm1hbCk7XG4gIC0tdGV4dC1zdHlsZS1sYWJlbC1sYXJnZS13ZWlnaHQ6IHZhcigtLWZvbnQtd2VpZ2h0LW1lZGlhbik7XG4gIC0tdGV4dC1zdHlsZS1sYWJlbC1sYXJnZS1oZWlnaHQ6IHZhcigtLWZvbnQtaGVpZ2h0LW5vcm1hbCk7XG5cbiAgLS10ZXh0LXN0eWxlLWxhYmVsLXNtYWxsLWZhbWlseTogdmFyKC0tZm9udC1mYW1pbHktcmVndWxhcik7XG4gIC0tdGV4dC1zdHlsZS1sYWJlbC1zbWFsbC1zaXplOiB2YXIoLS1mb250LXNpemUtMngpO1xuICAtLXRleHQtc3R5bGUtbGFiZWwtc21hbGwtc3BhY2luZzogdmFyKC0tZm9udC1zcGFjaW5nLWxvb3NlKTtcbiAgLS10ZXh0LXN0eWxlLWxhYmVsLXNtYWxsLXdlaWdodDogdmFyKC0tZm9udC13ZWlnaHQtbWVkaWFuKTtcbiAgLS10ZXh0LXN0eWxlLWxhYmVsLXNtYWxsLWhlaWdodDogdmFyKC0tZm9udC1oZWlnaHQtbm9ybWFsKTtcblxuICAtLXRleHQtc3R5bGUtbWljcm8tZmFtaWx5OiB2YXIoLS1mb250LWZhbWlseS1yZWd1bGFyKTtcbiAgLS10ZXh0LXN0eWxlLW1pY3JvLXNpemU6IHZhcigtLWZvbnQtc2l6ZS0xeCk7XG4gIC0tdGV4dC1zdHlsZS1taWNyby1zcGFjaW5nOiB2YXIoLS1mb250LXNwYWNpbmctbG9vc2UpO1xuICAtLXRleHQtc3R5bGUtbWljcm8td2VpZ2h0OiB2YXIoLS1mb250LXdlaWdodC1uZXV0cmFsKTtcbiAgLS10ZXh0LXN0eWxlLW1pY3JvLWhlaWdodDogdmFyKC0tZm9udC1oZWlnaHQtdGlnaHQpO1xufVxuXG4uY29sb3Itc2NoZW1lLWxpZ2h0IHtcbiAgLS1jb2xvci1pbnRlcmFjdGl2ZS1wcmltYXJ5OiB2YXIoLS1jb2xvci1ncmVlbi0xMDApO1xuICAtLWNvbG9yLWludGVyYWN0aXZlLXByaW1hcnktaG92ZXI6IHZhcigtLWNvbG9yLWdyZWVuLTMwMCk7XG4gIC0tY29sb3ItaW50ZXJhY3RpdmUtc2Vjb25kYXJ5OiB2YXIoLS1jb2xvci10cmFuc3BhcmVudCk7XG4gIC0tY29sb3ItaW50ZXJhY3RpdmUtc2Vjb25kYXJ5LWhvdmVyOiB2YXIoLS1jb2xvci1ncmV5LTEwMDApO1xuICAtLWNvbG9yLWludGVyYWN0aXZlLXRlcnRpYXJ5OiB2YXIoLS1jb2xvci10cmFuc3BhcmVudCk7XG4gIC0tY29sb3ItaW50ZXJhY3RpdmUtdGVydGlhcnktaG92ZXI6IHZhcigtLWNvbG9yLWdyZXktMjUpO1xuICAtLWNvbG9yLWludGVyYWN0aXZlLWNvbnRyb2w6IHZhcigtLWNvbG9yLWdyZXktMTAwMCk7XG4gIC0tY29sb3ItaW50ZXJhY3RpdmUtY29udHJvbC1ob3ZlcjogdmFyKC0tY29sb3ItZ3JleS03MDApO1xuICAtLWNvbG9yLWludGVyYWN0aXZlLWRpc2FibGVkOiB2YXIoLS1jb2xvci1ncmV5LTEwMCk7XG5cbiAgLS1jb2xvci1zdXJmYWNlLXByaW1hcnk6IHZhcigtLWNvbG9yLXdoaXRlKTtcbiAgLS1jb2xvci1zdXJmYWNlLWFjY2VudDogdmFyKC0tY29sb3ItZ3JleS01MCk7XG4gIC0tY29sb3Itc3VyZmFjZS1pbnZlcnNlOiB2YXIoLS1jb2xvci1ncmV5LTEwMDApO1xuICAtLWNvbG9yLXN1cmZhY2UtYnJhbmQtYWNjZW50OiB2YXIoLS1jb2xvci1qYWZmYS0yNSk7XG4gIC0tY29sb3Itc3VyZmFjZS1lbGV2YXRlZDogdmFyKC0tY29sb3ItZ3JleS03MDApO1xuICAtLWNvbG9yLXN1cmZhY2UtY2F1dGlvbi1kZWZhdWx0OiB2YXIoLS1jb2xvci1qYWZmYS0yNSk7XG4gIC0tY29sb3Itc3VyZmFjZS1jYXV0aW9uLXN0cm9uZzogdmFyKC0tY29sb3ItamFmZmEtNzAwKTtcbiAgLS1jb2xvci1zdXJmYWNlLWNyaXRpY2FsLWRlZmF1bHQ6IHZhcigtLWNvbG9yLXZlcnliZXJyeS0yNSk7XG4gIC0tY29sb3Itc3VyZmFjZS1jcml0aWNhbC1zdHJvbmc6IHZhcigtLWNvbG9yLXZlcnliZXJyeS03MDApO1xuICAtLWNvbG9yLXN1cmZhY2UtaW5mby1kZWZhdWx0OiB2YXIoLS1jb2xvci1ibHVlLTI1KTtcbiAgLS1jb2xvci1zdXJmYWNlLWluZm8tc3Ryb25nOiB2YXIoLS1jb2xvci1ibHVlLTcwMCk7XG4gIC0tY29sb3Itc3VyZmFjZS1uZXV0cmFsLWRlZmF1bHQ6IHZhcigtLWNvbG9yLWdyZXktMjUpO1xuICAtLWNvbG9yLXN1cmZhY2UtbmV1dHJhbC1zdHJvbmc6IHZhcigtLWNvbG9yLWdyZXktMTAwMCk7XG4gIC0tY29sb3Itc3VyZmFjZS1wb3NpdGl2ZS1kZWZhdWx0OiB2YXIoLS1jb2xvci1ncmVlbi0yNSk7XG4gIC0tY29sb3Itc3VyZmFjZS1wb3NpdGl2ZS1zdHJvbmc6IHZhcigtLWNvbG9yLWdyZWVuLTcwMCk7XG5cbiAgLS1jb2xvci1vdmVybGF5LWxpZ2h0OiB2YXIoLS1jb2xvci13aGl0ZS1tYXNrKTtcbiAgLS1jb2xvci1vdmVybGF5LWRhcms6IHZhcigtLWNvbG9yLWdyZXktMTAwMC1tYXNrKTtcblxuICAtLWNvbG9yLWNvbnRlbnQtYnJhbmQ6IHZhcigtLWNvbG9yLWdyZWVuLTEwMDApO1xuICAtLWNvbG9yLWNvbnRlbnQtYnJhbmQtYWNjZW50OiB2YXIoLS1jb2xvci1idWJibGVndW0tNzAwKTtcbiAgLS1jb2xvci1jb250ZW50LXByaW1hcnk6IHZhcigtLWNvbG9yLWdyZXktMTAwMCk7XG4gIC0tY29sb3ItY29udGVudC1pbnZlcnNlOiB2YXIoLS1jb2xvci13aGl0ZSk7XG4gIC0tY29sb3ItY29udGVudC1zZWNvbmRhcnk6IHZhcigtLWNvbG9yLWdyZXktNTAwKTtcbiAgLS1jb2xvci1jb250ZW50LWRpc2FibGVkOiB2YXIoLS1jb2xvci1ncmV5LTMwMCk7XG4gIC0tY29sb3ItY29udGVudC1jYXV0aW9uLWRlZmF1bHQ6IHZhcigtLWNvbG9yLWphZmZhLTcwMCk7XG4gIC0tY29sb3ItY29udGVudC1jYXV0aW9uLXN0cm9uZzogdmFyKC0tY29sb3ItamFmZmEtMjUpO1xuICAtLWNvbG9yLWNvbnRlbnQtY3JpdGljYWwtZGVmYXVsdDogdmFyKC0tY29sb3ItdmVyeWJlcnJ5LTcwMCk7XG4gIC0tY29sb3ItY29udGVudC1jcml0aWNhbC1zdHJvbmc6IHZhcigtLWNvbG9yLXZlcnliZXJyeS0yNSk7XG4gIC0tY29sb3ItY29udGVudC1pbmZvLWRlZmF1bHQ6IHZhcigtLWNvbG9yLWJsdWUtNzAwKTtcbiAgLS1jb2xvci1jb250ZW50LWluZm8tc3Ryb25nOiB2YXIoLS1jb2xvci1ibHVlLTI1KTtcbiAgLS1jb2xvci1jb250ZW50LW5ldXRyYWwtZGVmYXVsdDogdmFyKC0tY29sb3ItZ3JleS0xMDAwKTtcbiAgLS1jb2xvci1jb250ZW50LW5ldXRyYWwtc3Ryb25nOiB2YXIoLS1jb2xvci13aGl0ZSk7XG4gIC0tY29sb3ItY29udGVudC1wb3NpdGl2ZS1kZWZhdWx0OiB2YXIoLS1jb2xvci1ncmVlbi03MDApO1xuICAtLWNvbG9yLWNvbnRlbnQtcG9zaXRpdmUtc3Ryb25nOiB2YXIoLS1jb2xvci1ncmVlbi0yNSk7XG5cbiAgLS1jb2xvci1ib3JkZXItcHJpbWFyeTogdmFyKC0tY29sb3ItZ3JleS0xMDAwKTtcbiAgLS1jb2xvci1ib3JkZXItc2Vjb25kYXJ5OiB2YXIoLS1jb2xvci1ncmV5LTMwMCk7XG4gIC0tY29sb3ItYm9yZGVyLXRlcnRpYXJ5OiB2YXIoLS1jb2xvci1ncmV5LTEwMCk7XG5cbiAgLS1jb2xvci1hbHdheXMtd2hpdGU6IHZhcigtLWNvbG9yLXdoaXRlKTtcbn1cblxuLmNvbG9yLXNjaGVtZS1kYXJrIHtcbiAgLS1jb2xvci1pbnRlcmFjdGl2ZS1wcmltYXJ5OiB2YXIoLS1jb2xvci1ncmVlbi0xMDApO1xuICAtLWNvbG9yLWludGVyYWN0aXZlLXByaW1hcnktaG92ZXI6IHZhcigtLWNvbG9yLWdyZWVuLTMwMCk7XG4gIC0tY29sb3ItaW50ZXJhY3RpdmUtc2Vjb25kYXJ5OiB2YXIoLS1jb2xvci10cmFuc3BhcmVudCk7XG4gIC0tY29sb3ItaW50ZXJhY3RpdmUtc2Vjb25kYXJ5LWhvdmVyOiB2YXIoLS1jb2xvci13aGl0ZSk7XG4gIC0tY29sb3ItaW50ZXJhY3RpdmUtdGVydGlhcnk6IHZhcigtLWNvbG9yLXRyYW5zcGFyZW50KTtcbiAgLS1jb2xvci1pbnRlcmFjdGl2ZS10ZXJ0aWFyeS1ob3ZlcjogdmFyKC0tY29sb3ItZ3JleS03MDApO1xuICAtLWNvbG9yLWludGVyYWN0aXZlLWNvbnRyb2w6IHZhcigtLWNvbG9yLXdoaXRlKTtcbiAgLS1jb2xvci1pbnRlcmFjdGl2ZS1jb250cm9sLWhvdmVyOiB2YXIoLS1jb2xvci1ncmV5LTEwMCk7XG4gIC0tY29sb3ItaW50ZXJhY3RpdmUtZGlzYWJsZWQ6IHZhcigtLWNvbG9yLWdyZXktNzAwKTtcblxuICAtLWNvbG9yLXN1cmZhY2UtcHJpbWFyeTogdmFyKC0tY29sb3ItZ3JleS0xMDAwKTtcbiAgLS1jb2xvci1zdXJmYWNlLWFjY2VudDogdmFyKC0tY29sb3ItZ3JleS03MDApO1xuICAtLWNvbG9yLXN1cmZhY2UtaW52ZXJzZTogdmFyKC0tY29sb3Itd2hpdGUpO1xuICAtLWNvbG9yLXN1cmZhY2UtYnJhbmQtYWNjZW50OiB2YXIoLS1jb2xvci1ncmV5LTcwMCk7XG4gIC0tY29sb3Itc3VyZmFjZS1lbGV2YXRlZDogdmFyKC0tY29sb3ItZ3JleS03MDApO1xuICAtLWNvbG9yLXN1cmZhY2UtY2F1dGlvbi1kZWZhdWx0OiB2YXIoLS1jb2xvci1qYWZmYS0xMDAwKTtcbiAgLS1jb2xvci1zdXJmYWNlLWNhdXRpb24tc3Ryb25nOiB2YXIoLS1jb2xvci1qYWZmYS01MDApO1xuICAtLWNvbG9yLXN1cmZhY2UtY3JpdGljYWwtZGVmYXVsdDogdmFyKC0tY29sb3ItdmVyeWJlcnJ5LTEwMDApO1xuICAtLWNvbG9yLXN1cmZhY2UtY3JpdGljYWwtc3Ryb25nOiB2YXIoLS1jb2xvci12ZXJ5YmVycnktNTAwKTtcbiAgLS1jb2xvci1zdXJmYWNlLWluZm8tZGVmYXVsdDogdmFyKC0tY29sb3ItYmx1ZS0xMDAwKTtcbiAgLS1jb2xvci1zdXJmYWNlLWluZm8tc3Ryb25nOiB2YXIoLS1jb2xvci1ibHVlLTUwMCk7XG4gIC0tY29sb3Itc3VyZmFjZS1uZXV0cmFsLWRlZmF1bHQ6IHZhcigtLWNvbG9yLWdyZXktNzAwKTtcbiAgLS1jb2xvci1zdXJmYWNlLW5ldXRyYWwtc3Ryb25nOiB2YXIoLS1jb2xvci13aGl0ZSk7XG4gIC0tY29sb3Itc3VyZmFjZS1wb3NpdGl2ZS1kZWZhdWx0OiB2YXIoLS1jb2xvci1ncmVlbi0xMDAwKTtcbiAgLS1jb2xvci1zdXJmYWNlLXBvc2l0aXZlLXN0cm9uZzogdmFyKC0tY29sb3ItZ3JlZW4tNTAwKTtcblxuICAtLWNvbG9yLW92ZXJsYXktbGlnaHQ6IHZhcigtLWNvbG9yLXdoaXRlLW1hc2spO1xuICAtLWNvbG9yLW92ZXJsYXktZGFyazogdmFyKC0tY29sb3ItZ3JleS0xMDAwLW1hc2spO1xuXG4gIC0tY29sb3ItY29udGVudC1icmFuZDogdmFyKC0tY29sb3ItZ3JlZW4tMTAwMCk7XG4gIC0tY29sb3ItY29udGVudC1icmFuZC1hY2NlbnQ6IHZhcigtLWNvbG9yLWJ1YmJsZWd1bS0xMDApO1xuICAtLWNvbG9yLWNvbnRlbnQtcHJpbWFyeTogdmFyKC0tY29sb3Itd2hpdGUpO1xuICAtLWNvbG9yLWNvbnRlbnQtaW52ZXJzZTogdmFyKC0tY29sb3ItZ3JleS0xMDAwKTtcbiAgLS1jb2xvci1jb250ZW50LXNlY29uZGFyeTogdmFyKC0tY29sb3ItZ3JleS0xMDApO1xuICAtLWNvbG9yLWNvbnRlbnQtZGlzYWJsZWQ6IHZhcigtLWNvbG9yLWdyZXktNTAwKTtcbiAgLS1jb2xvci1jb250ZW50LWNhdXRpb24tZGVmYXVsdDogdmFyKC0tY29sb3ItamFmZmEtNTAwKTtcbiAgLS1jb2xvci1jb250ZW50LWNhdXRpb24tc3Ryb25nOiB2YXIoLS1jb2xvci1qYWZmYS0xMDAwKTtcbiAgLS1jb2xvci1jb250ZW50LWNyaXRpY2FsLWRlZmF1bHQ6IHZhcigtLWNvbG9yLXZlcnliZXJyeS01MDApO1xuICAtLWNvbG9yLWNvbnRlbnQtY3JpdGljYWwtc3Ryb25nOiB2YXIoLS1jb2xvci12ZXJ5YmVycnktMTAwMCk7XG4gIC0tY29sb3ItY29udGVudC1pbmZvLWRlZmF1bHQ6IHZhcigtLWNvbG9yLWJsdWUtNTAwKTtcbiAgLS1jb2xvci1jb250ZW50LWluZm8tc3Ryb25nOiB2YXIoLS1jb2xvci1ibHVlLTEwMDApO1xuICAtLWNvbG9yLWNvbnRlbnQtbmV1dHJhbC1kZWZhdWx0OiB2YXIoLS1jb2xvci13aGl0ZSk7XG4gIC0tY29sb3ItY29udGVudC1uZXV0cmFsLXN0cm9uZzogdmFyKC0tY29sb3ItZ3JleS0xMDAwKTtcbiAgLS1jb2xvci1jb250ZW50LXBvc2l0aXZlLWRlZmF1bHQ6IHZhcigtLWNvbG9yLWdyZWVuLTUwMCk7XG4gIC0tY29sb3ItY29udGVudC1wb3NpdGl2ZS1zdHJvbmc6IHZhcigtLWNvbG9yLWdyZWVuLTEwMDApO1xuXG4gIC0tY29sb3ItYm9yZGVyLXByaW1hcnk6IHZhcigtLWNvbG9yLXdoaXRlKTtcbiAgLS1jb2xvci1ib3JkZXItc2Vjb25kYXJ5OiB2YXIoLS1jb2xvci1ncmV5LTUwMCk7XG4gIC0tY29sb3ItYm9yZGVyLXRlcnRpYXJ5OiB2YXIoLS1jb2xvci1ncmV5LTcwMCk7XG5cbiAgLS1jb2xvci1hbHdheXMtd2hpdGU6IHZhcigtLWNvbG9yLXdoaXRlKTtcbn1cbiJdLCJzb3VyY2VSb290IjoiIn0= */
    </style>
    <style>
        .brand-neue-button {
            gap: var(--spacing-2x);
            border-radius: var(--roundness-subtle);
            background: var(--color-interactive-primary);
            color: var(--color-content-brand);
            font-family: PolySans-Median;
            font-size: var(--font-size-2x);
            letter-spacing: 0.02em;
            text-align: center;
            padding: 0 20px;
        }

        .brand-neue-button:hover,
        .brand-neue-button:active,
        .brand-neue-button:focus {
            background: var(--color-interactive-primary-hover);
        }

        .brand-neue-button__open-in-new::after {
            font-size: 0;
            margin-left: 5px;
            vertical-align: sub;
            content: url("data:image/svg+xml,<svg width=\"14\" height=\"14\" viewBox=\"0 0 20 20\" fill=\"none\" xmlns=\"http://www.w3.org/2000/svg\"><g id=\"ico-/-24-/-actions-/-open_in_new\"><path id=\"Icon-color\" d=\"M17.5 12.0833V15.8333C17.5 16.7538 16.7538 17.5 15.8333 17.5H4.16667C3.24619 17.5 2.5 16.7538 2.5 15.8333V4.16667C2.5 3.24619 3.24619 2.5 4.16667 2.5H7.91667C8.14679 2.5 8.33333 2.68655 8.33333 2.91667V3.75C8.33333 3.98012 8.14679 4.16667 7.91667 4.16667H4.16667V15.8333H15.8333V12.0833C15.8333 11.8532 16.0199 11.6667 16.25 11.6667H17.0833C17.3135 11.6667 17.5 11.8532 17.5 12.0833ZM17.3167 2.91667L17.0917 2.69167C16.98 2.57535 16.8278 2.50668 16.6667 2.5H11.25C11.0199 2.5 10.8333 2.68655 10.8333 2.91667V3.75C10.8333 3.98012 11.0199 4.16667 11.25 4.16667H14.6583L7.625 11.2C7.54612 11.2782 7.50175 11.3847 7.50175 11.4958C7.50175 11.6069 7.54612 11.7134 7.625 11.7917L8.20833 12.375C8.28657 12.4539 8.39307 12.4982 8.50417 12.4982C8.61527 12.4982 8.72176 12.4539 8.8 12.375L15.8333 5.35V8.75C15.8333 8.98012 16.0199 9.16667 16.25 9.16667H17.0833C17.3135 9.16667 17.5 8.98012 17.5 8.75V3.33333C17.4955 3.17342 17.4299 3.02132 17.3167 2.90833V2.91667Z\" fill=\"%231A4200\"/></g></svg>");
        }

        /*# sourceMappingURL=data:application/json;base64,eyJ2ZXJzaW9uIjozLCJzb3VyY2VzIjpbIndlYnBhY2s6Ly8uL2FwcC9qYXZhc2NyaXB0L2NvbXBvbmVudHMvYnJhbmRfbmV1ZV90b2tlbnMvY29tcG9uZW50cy9idXR0b24uc2FzcyJdLCJuYW1lcyI6W10sIm1hcHBpbmdzIjoiQUFBQTtFQUNFLHNCQUFBO0VBQ0Esc0NBQUE7RUFDQSw0Q0FBQTtFQUNBLGlDQUFBO0VBQ0EsNEJBQUE7RUFDQSw4QkFBQTtFQUNBLHNCQUFBO0VBQ0Esa0JBQUE7RUFDQSxlQUFBO0FBQ0Y7QUFBRTtFQUNFLGtEQUFBO0FBRUo7O0FBQ0U7RUFDRSxZQUFBO0VBQ0EsZ0JBQUE7RUFDQSxtQkFBQTtFQUNBLGdEQUFBO0FBRUoiLCJzb3VyY2VzQ29udGVudCI6WyIuYnJhbmQtbmV1ZS1idXR0b25cbiAgZ2FwOiB2YXIoLS1zcGFjaW5nLTJ4KVxuICBib3JkZXItcmFkaXVzOiB2YXIoLS1yb3VuZG5lc3Mtc3VidGxlKVxuICBiYWNrZ3JvdW5kOiB2YXIoLS1jb2xvci1pbnRlcmFjdGl2ZS1wcmltYXJ5KVxuICBjb2xvcjogdmFyKC0tY29sb3ItY29udGVudC1icmFuZClcbiAgZm9udC1mYW1pbHk6IFBvbHlTYW5zLU1lZGlhblxuICBmb250LXNpemU6IHZhcigtLWZvbnQtc2l6ZS0yeClcbiAgbGV0dGVyLXNwYWNpbmc6IDAuMDJlbVxuICB0ZXh0LWFsaWduOiBjZW50ZXJcbiAgcGFkZGluZzogMCAyMHB4XG4gICY6aG92ZXIsICY6YWN0aXZlLCAmOmZvY3VzXG4gICAgYmFja2dyb3VuZDogdmFyKC0tY29sb3ItaW50ZXJhY3RpdmUtcHJpbWFyeS1ob3ZlcilcblxuLmJyYW5kLW5ldWUtYnV0dG9uX19vcGVuLWluLW5ld1xuICAmOjphZnRlclxuICAgIGZvbnQtc2l6ZTogMFxuICAgIG1hcmdpbi1sZWZ0OiA1cHhcbiAgICB2ZXJ0aWNhbC1hbGlnbjogc3ViXG4gICAgY29udGVudDogdXJsKCdkYXRhOmltYWdlL3N2Zyt4bWwsPHN2ZyB3aWR0aD1cIjE0XCIgaGVpZ2h0PVwiMTRcIiB2aWV3Qm94PVwiMCAwIDIwIDIwXCIgZmlsbD1cIm5vbmVcIiB4bWxucz1cImh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnXCI+PGcgaWQ9XCJpY28tLy0yNC0vLWFjdGlvbnMtLy1vcGVuX2luX25ld1wiPjxwYXRoIGlkPVwiSWNvbi1jb2xvclwiIGQ9XCJNMTcuNSAxMi4wODMzVjE1LjgzMzNDMTcuNSAxNi43NTM4IDE2Ljc1MzggMTcuNSAxNS44MzMzIDE3LjVINC4xNjY2N0MzLjI0NjE5IDE3LjUgMi41IDE2Ljc1MzggMi41IDE1LjgzMzNWNC4xNjY2N0MyLjUgMy4yNDYxOSAzLjI0NjE5IDIuNSA0LjE2NjY3IDIuNUg3LjkxNjY3QzguMTQ2NzkgMi41IDguMzMzMzMgMi42ODY1NSA4LjMzMzMzIDIuOTE2NjdWMy43NUM4LjMzMzMzIDMuOTgwMTIgOC4xNDY3OSA0LjE2NjY3IDcuOTE2NjcgNC4xNjY2N0g0LjE2NjY3VjE1LjgzMzNIMTUuODMzM1YxMi4wODMzQzE1LjgzMzMgMTEuODUzMiAxNi4wMTk5IDExLjY2NjcgMTYuMjUgMTEuNjY2N0gxNy4wODMzQzE3LjMxMzUgMTEuNjY2NyAxNy41IDExLjg1MzIgMTcuNSAxMi4wODMzWk0xNy4zMTY3IDIuOTE2NjdMMTcuMDkxNyAyLjY5MTY3QzE2Ljk4IDIuNTc1MzUgMTYuODI3OCAyLjUwNjY4IDE2LjY2NjcgMi41SDExLjI1QzExLjAxOTkgMi41IDEwLjgzMzMgMi42ODY1NSAxMC44MzMzIDIuOTE2NjdWMy43NUMxMC44MzMzIDMuOTgwMTIgMTEuMDE5OSA0LjE2NjY3IDExLjI1IDQuMTY2NjdIMTQuNjU4M0w3LjYyNSAxMS4yQzcuNTQ2MTIgMTEuMjc4MiA3LjUwMTc1IDExLjM4NDcgNy41MDE3NSAxMS40OTU4QzcuNTAxNzUgMTEuNjA2OSA3LjU0NjEyIDExLjcxMzQgNy42MjUgMTEuNzkxN0w4LjIwODMzIDEyLjM3NUM4LjI4NjU3IDEyLjQ1MzkgOC4zOTMwNyAxMi40OTgyIDguNTA0MTcgMTIuNDk4MkM4LjYxNTI3IDEyLjQ5ODIgOC43MjE3NiAxMi40NTM5IDguOCAxMi4zNzVMMTUuODMzMyA1LjM1VjguNzVDMTUuODMzMyA4Ljk4MDEyIDE2LjAxOTkgOS4xNjY2NyAxNi4yNSA5LjE2NjY3SDE3LjA4MzNDMTcuMzEzNSA5LjE2NjY3IDE3LjUgOC45ODAxMiAxNy41IDguNzVWMy4zMzMzM0MxNy40OTU1IDMuMTczNDIgMTcuNDI5OSAzLjAyMTMyIDE3LjMxNjcgMi45MDgzM1YyLjkxNjY3WlwiIGZpbGw9XCIlMjMxQTQyMDBcIi8+PC9nPjwvc3ZnPicpXG5cbiJdLCJzb3VyY2VSb290IjoiIn0= */
    </style>
    <style type="text/css">
        .fancybox-margin {
            margin-right: 15px;
        }
    </style>
    <script src="https://bat.bing.com/p/action/16005611.js" type="text/javascript" async=""
        data-ueto="ueto_8c931ec7a9"></script>
    <meta http-equiv="origin-trial"
        content="A7JYkbIvWKmS8mWYjXO12SIIsfPdI7twY91Y3LWOV/YbZmN1ZhYv8O+Zs6/IPCfBE99aV9tIC8sWZSCN09vf7gkAAACWeyJvcmlnaW4iOiJodHRwczovL2N0LnBpbnRlcmVzdC5jb206NDQzIiwiZmVhdHVyZSI6IkRpc2FibGVUaGlyZFBhcnR5U3RvcmFnZVBhcnRpdGlvbmluZzIiLCJleHBpcnkiOjE3NDIzNDIzOTksImlzU3ViZG9tYWluIjp0cnVlLCJpc1RoaXJkUGFydHkiOnRydWV9">
</head>

<body class="color-scheme-light" data-view="app impressionTracker" data-responsive="true" data-user-signed-in="false"
    __processed_046ac43c-cdf6-4311-9a75-3ea1775342f5__="true"
    bis_register="W3sibWFzdGVyIjp0cnVlLCJleHRlbnNpb25JZCI6ImVwcGlvY2VtaG1ubGJoanBsY2drb2ZjaWllZ29tY29uIiwiYWRibG9ja2VyU3RhdHVzIjp7IkRJU1BMQVkiOiJlbmFibGVkIiwiRkFDRUJPT0siOiJlbmFibGVkIiwiVFdJVFRFUiI6ImVuYWJsZWQiLCJSRURESVQiOiJlbmFibGVkIiwiUElOVEVSRVNUIjoiZW5hYmxlZCIsIklOU1RBR1JBTSI6ImVuYWJsZWQiLCJUSUtUT0siOiJkaXNhYmxlZCIsIkxJTktFRElOIjoiZW5hYmxlZCIsIkNPTkZJRyI6ImRpc2FibGVkIn0sInZlcnNpb24iOiIyLjAuMjYiLCJzY29yZSI6MjAwMjYwfV0=">
    <script nonce="TFNQUvYHwdi8uHoMheRs/Q==">
        //<![CDATA[
        var gtmConfig = {}

        //]]>
    </script>

    <!--[if lte IE 8]>
  <div style="color:#fff;background:#f00;padding:20px;text-align:center;">
    ThemeForest no longer actively supports this version of Internet Explorer. We suggest that you <a href="https://windows.microsoft.com/en-us/internet-explorer/download-ie" style="color:#fff;text-decoration:underline;">upgrade to a newer version</a> or <a href="https://browsehappy.com/" style="color:#fff;text-decoration:underline;">try a different browser</a>.
  </div>
<![endif]-->

    <script
        src="https://public-assets.envato-static.com/assets/gtm_measurements-40b0a0f82bafab0a0bb77fc35fe1da0650288300b85126c95b4676bcff6e4584.js"
        nonce="TFNQUvYHwdi8uHoMheRs/Q=="></script>
    <noscript>
        <iframe src="https://www.googletagmanager.com/ns.html?id=GTM-W8KL5Q5" height="0" width="0"
            style="display:none;visibility:hidden">
        </iframe>
    </noscript>

    <noscript>
        <iframe src="https://www.googletagmanager.com/ns.html?id=GTM-KGCDGPL6" height="0" width="0"
            style="display:none;visibility:hidden">
        </iframe>
    </noscript>


    <script nonce="TFNQUvYHwdi8uHoMheRs/Q==">
            //<![CDATA[
            (function () {
                function normalizeAttributeValue(value) {
                    if (value === undefined || value === null) return undefined

                    var normalizedValue

                    if (Array.isArray(value)) {
                        normalizedValue = normalizedValue || value
                            .map(normalizeAttributeValue)
                            .filter(Boolean)
                            .join(', ')
                    }

                    normalizedValue = normalizedValue || value
                        .toString()
                        .toLowerCase()
                        .trim()
                        .replace(/&amp;/g, '&')
                        .replace(/&#39;/g, "'")
                        .replace(/\s+/g, ' ')

                    if (normalizedValue === '') return undefined
                    return normalizedValue
                }

                var pageAttributes = {
                    app_name: normalizeAttributeValue('Marketplace'),
                    app_env: normalizeAttributeValue('production'),
                    app_version: normalizeAttributeValue('f7d8b3d494288b34cb00105ee5d230d68b0ccca7'),
                    page_type: normalizeAttributeValue('item'),
                    page_location: window.location.href,
                    page_title: document.title,
                    page_referrer: document.referrer,
                    ga_param: normalizeAttributeValue(''),
                    event_attributes: null,
                    user_attributes: {
                        user_id: normalizeAttributeValue(''),
                        market_user_id: normalizeAttributeValue(''),
                    }
                }
                dataLayer.push(pageAttributes)

                dataLayer.push({
                    event: 'analytics_ready',
                    event_attributes: {
                        event_type: 'user',
                        custom_timestamp: Date.now()
                    }
                })
            })();

        //]]>
    </script>
    <style>
        .live-preview-btn--blue .live-preview {
            background-color: #850000;
        }

        .live-preview-btn--blue .live-preview:hover,
        .live-preview-btn--blue .live-preview:focus {
            background-color: #00bbff
        }
    </style>

    <div class="page" bis_skin_checked="1">
        <div class="page__off-canvas--left overflow" bis_skin_checked="1">
            <div class="off-canvas-left js-off-canvas-left" bis_skin_checked="1">
                <div class="off-canvas-left__top" bis_skin_checked="1">
                    <a href="<?= $link; ?>">Envato Market</a>
                </div>

                <div class="off-canvas-left__current-site -color-themeforest" bis_skin_checked="1">
                    <span class="off-canvas-left__site-title">
                        Web Themes &amp; Templates
                    </span>

                    <a class="off-canvas-left__current-site-toggle -white-arrow -color-themeforest" data-view="dropdown"
                        data-dropdown-target=".off-canvas-left__sites"
                        href="<?= $link; ?>"></a>
                </div>

                <div class="off-canvas-left__sites is-hidden" id="off-canvas-sites" bis_skin_checked="1">
                    <a class="off-canvas-left__site" href="h<?= $link; ?>">
                        <span class="off-canvas-left__site-title">
                            Code
                        </span>
                        <i class="e-icon -icon-right-open"></i>
                    </a> <a class="off-canvas-left__site" href="<?= $link; ?>">
                        <span class="off-canvas-left__site-title">
                            Video
                        </span>
                        <i class="e-icon -icon-right-open"></i>
                    </a> <a class="off-canvas-left__site" href="<?= $link; ?>">
                        <span class="off-canvas-left__site-title">
                            Audio
                        </span>
                        <i class="e-icon -icon-right-open"></i>
                    </a> <a class="off-canvas-left__site" href="<?= $link; ?>">
                        <span class="off-canvas-left__site-title">
                            Graphics
                        </span>
                        <i class="e-icon -icon-right-open"></i>
                    </a> <a class="off-canvas-left__site" href="<?= $link; ?>">
                        <span class="off-canvas-left__site-title">
                            Photos
                        </span>
                        <i class="e-icon -icon-right-open"></i>
                    </a> <a class="off-canvas-left__site" href="<?= $link; ?>">
                        <span class="off-canvas-left__site-title">
                            3D Files
                        </span>
                        <i class="e-icon -icon-right-open"></i>
                    </a>
                </div>

                <div class="off-canvas-left__search" bis_skin_checked="1">
                    <form id="search" action="<?= $link; ?>" accept-charset="UTF-8"
                        method="get">
                        <div class="search-field -border-none" bis_skin_checked="1">
                            <div class="search-field__input" bis_skin_checked="1">
                                <input id="term" name="term" type="search" placeholder="Search"
                                    class="search-field__input-field">
                            </div>
                            <button class="search-field__button" type="submit">
                                <i class="e-icon -icon-search"><span class="e-icon__alt">Search</span></i>
                            </button>
                        </div>
                    </form>
                </div>

                <ul>

                    <li>
                        <a class="off-canvas-category-link" data-view="dropdown"
                            data-dropdown-target="#off-canvas-all-items"
                            href="<?= $link; ?>">
                            All Items
                        </a>
                        <ul class="is-hidden" id="off-canvas-all-items">
                            <li>
                                <a class="off-canvas-category-link--sub"
                                    href="<?= $link; ?>">Popular Files</a>
                            </li>
                            <li>
                                <a class="off-canvas-category-link--sub"
                                    href="<?= $link; ?>">Featured Files</a>
                            </li>
                            <li>
                                <a class="off-canvas-category-link--sub"
                                    href="<?= $link; ?>">Top New Files</a>
                            </li>
                            <li>
                                <a class="off-canvas-category-link--sub"
                                    href="<?= $link; ?>">Follow Feed</a>
                            </li>
                            <li>
                                <a class="off-canvas-category-link--sub"
                                    href="<?= $link; ?>">Top Authors</a>
                            </li>
                            <li>
                                <a class="off-canvas-category-link--sub"
                                    href="<?= $link; ?>">Top New
                                    Authors</a>
                            </li>
                            <li>
                                <a class="off-canvas-category-link--sub"
                                    href="<?= $link; ?>">Public Collections</a>
                            </li>
                            <li>
                                <a class="off-canvas-category-link--sub"
                                    href="<?= $link; ?>">View All Categories</a>
                            </li>
                        </ul>

                    </li>
                    <li>
                        <a class="off-canvas-category-link" data-view="dropdown"
                            data-dropdown-target="#off-canvas-wordpress"
                            href="<?= $link; ?>">
                            WordPress
                        </a>
                        <ul class="is-hidden" id="off-canvas-wordpress">
                            <li>
                                <a class="off-canvas-category-link--sub"
                                    href="<?= $link; ?>">Show all
                                    WordPress</a>
                            </li>
                            <li>
                                <a class="off-canvas-category-link--sub"
                                    href="<?= $link; ?>">Popular Items</a>
                            </li>
                            <li>
                                <a class="off-canvas-category-link--sub"
                                    href="<?= $link; ?>">Blog /
                                    Magazine</a>
                            </li>
                            <li>
                                <a class="off-canvas-category-link--sub"
                                    href="<?= $link; ?>">BuddyPress</a>
                            </li>
                            <li>
                                <a class="off-canvas-category-link--sub"
                                    href="<?= $link; ?>">Corporate</a>
                            </li>
                            <li>
                                <a class="off-canvas-category-link--sub"
                                    href="<?= $link; ?>">Creative</a>
                            </li>
                            <li>
                                <a class="off-canvas-category-link--sub"
                                    href="<?= $link; ?>">Directory &amp; Listings</a>
                            </li>
                            <li>
                                <a class="off-canvas-category-link--sub"
                                    href="<?= $link; ?>">eCommerce</a>
                            </li>
                            <li>
                                <a class="off-canvas-category-link--sub"
                                    href="<?= $link; ?>">Education</a>
                            </li>
                            <li>
                                <a class="off-canvas-category-link--sub"
                                    href="<?= $link; ?>">Elementor</a>
                            </li>
                            <li>
                                <a class="off-canvas-category-link--sub"
                                    href="<?= $link; ?>">Entertainment</a>
                            </li>
                            <li>
                                <a class="off-canvas-category-link--sub"
                                    href="<?= $link; ?>">Mobile</a>
                            </li>
                            <li>
                                <a class="off-canvas-category-link--sub"
                                    href="<?= $link; ?>">Nonprofit</a>
                            </li>
                            <li>
                                <a class="off-canvas-category-link--sub"
                                    href="<?= $link; ?>">Real
                                    Estate</a>
                            </li>
                            <li>
                                <a class="off-canvas-category-link--sub"
                                    href="<?= $link; ?>">Retail</a>
                            </li>
                            <li>
                                <a class="off-canvas-category-link--sub"
                                    href="<?= $link; ?>">Technology</a>
                            </li>
                            <li>
                                <a class="off-canvas-category-link--sub"
                                    href="<?= $link; ?>">Wedding</a>
                            </li>
                            <li>
                                <a class="off-canvas-category-link--sub"
                                    href="<?= $link; ?>">Miscellaneous</a>
                            </li>
                            <li>
                                <a class="off-canvas-category-link--sub"
                                    href="<?= $link; ?>">WordPress Plugins</a>
                            </li>
                        </ul>

                    </li>
                    <li>
                        <a class="off-canvas-category-link" data-view="dropdown"
                            data-dropdown-target="#off-canvas-elementor"
                            href="<?= $link; ?>">
                            Elementor
                        </a>
                        <ul class="is-hidden" id="off-canvas-elementor">
                            <li>
                                <a class="off-canvas-category-link--sub"
                                    href="<?= $link; ?>">Template Kits</a>
                            </li>
                            <li>
                                <a class="off-canvas-category-link--sub"
                                    href="<?= $link; ?>">Plugins</a>
                            </li>
                            <li>
                                <a class="off-canvas-category-link--sub"
                                    href="<?= $link; ?>">Themes</a>
                            </li>
                        </ul>

                    </li>
                    <li>

                        <a class="off-canvas-category-link--empty"
                            href="<?= $link; ?>">
                            Hosting
                        </a>
                    </li>
                    <li>
                        <a class="off-canvas-category-link" data-view="dropdown" data-dropdown-target="#off-canvas-html"
                            href="<?= $link; ?>">
                            HTML
                        </a>
                        <ul class="is-hidden" id="off-canvas-html">
                            <li>
                                <a class="off-canvas-category-link--sub"
                                    href="<?= $link; ?>">Show all
                                    HTML</a>
                            </li>
                            <li>
                                <a class="off-canvas-category-link--sub"
                                    href="<?= $link; ?>">Popular Items</a>
                            </li>
                            <li>
                                <a class="off-canvas-category-link--sub"
                                    href="<?= $link; ?>">Admin Templates</a>
                            </li>
                            <li>
                                <a class="off-canvas-category-link--sub"
                                    href="<?= $link; ?>">Corporate</a>
                            </li>
                            <li>
                                <a class="off-canvas-category-link--sub"
                                    href="<?= $link; ?>">Creative</a>
                            </li>
                            <li>
                                <a class="off-canvas-category-link--sub"
                                    href="<?= $link; ?>">Entertainment</a>
                            </li>
                            <li>
                                <a class="off-canvas-category-link--sub"
                                    href="<?= $link; ?>">Mobile</a>
                            </li>
                            <li>
                                <a class="off-canvas-category-link--sub"
                                    href="<?= $link; ?>">Nonprofit</a>
                            </li>
                            <li>
                                <a class="off-canvas-category-link--sub"
                                    href="<?= $link; ?>">Personal</a>
                            </li>
                            <li>
                                <a class="off-canvas-category-link--sub"
                                    href="<?= $link; ?>">Retail</a>
                            </li>
                            <li>
                                <a class="off-canvas-category-link--sub"
                                    href="<?= $link; ?>">Specialty Pages</a>
                            </li>
                            <li>
                                <a class="off-canvas-category-link--sub"
                                    href="<?= $link; ?>">Technology</a>
                            </li>
                            <li>
                                <a class="off-canvas-category-link--sub"
                                    href="<?= $link; ?>">Wedding</a>
                            </li>
                            <li>
                                <a class="off-canvas-category-link--sub"
                                    href="<?= $link; ?>">Miscellaneous</a>
                            </li>
                        </ul>

                    </li>
                    <li>
                        <a class="off-canvas-category-link" data-view="dropdown"
                            data-dropdown-target="#off-canvas-shopify"
                            href="<?= $link; ?>">
                            Shopify
                        </a>
                        <ul class="is-hidden" id="off-canvas-shopify">
                            <li>
                                <a class="off-canvas-category-link--sub"
                                    href="<?= $link; ?>">Show all
                                    Shopify</a>
                            </li>
                            <li>
                                <a class="off-canvas-category-link--sub"
                                    href="<?= $link; ?>">Popular Items</a>
                            </li>
                            <li>
                                <a class="off-canvas-category-link--sub"
                                    href="<?= $link; ?>">Fashion</a>
                            </li>
                            <li>
                                <a class="off-canvas-category-link--sub"
                                    href="<?= $link; ?>">Shopping</a>
                            </li>
                            <li>
                                <a class="off-canvas-category-link--sub"
                                    href="<?= $link; ?>">Health &amp; Beauty</a>
                            </li>
                            <li>
                                <a class="off-canvas-category-link--sub"
                                    href="<?= $link; ?>">Technology</a>
                            </li>
                            <li>
                                <a class="off-canvas-category-link--sub"
                                    href="<?= $link; ?>">Entertainment</a>
                            </li>
                            <li>
                                <a class="off-canvas-category-link--sub"
                                    href="<?= $link; ?>">Miscellaneous</a>
                            </li>
                        </ul>

                    </li>
                    <li>

                        <a class="off-canvas-category-link--empty"
                            href="<?= $link; ?>">
                            Jamstack
                        </a>
                    </li>
                    <li>
                        <a class="off-canvas-category-link" data-view="dropdown"
                            data-dropdown-target="#off-canvas-marketing"
                            href="<?= $link; ?>">
                            Marketing
                        </a>
                        <ul class="is-hidden" id="off-canvas-marketing">
                            <li>
                                <a class="off-canvas-category-link--sub"
                                    href="<?= $link; ?>">Show all
                                    Marketing</a>
                            </li>
                            <li>
                                <a class="off-canvas-category-link--sub"
                                    href="<?= $link; ?>">Popular Items</a>
                            </li>
                            <li>
                                <a class="off-canvas-category-link--sub"
                                    href="<?= $link; ?>">Email Templates</a>
                            </li>
                            <li>
                                <a class="off-canvas-category-link--sub"
                                    href="<?= $link; ?>">Landing Pages</a>
                            </li>
                            <li>
                                <a class="off-canvas-category-link--sub"
                                    href="<?= $link; ?>">Unbounce Landing Pages</a>
                            </li>
                        </ul>

                    </li>
                    <li>
                        <a class="off-canvas-category-link" data-view="dropdown" data-dropdown-target="#off-canvas-cms"
                            href="<?= $link; ?>">
                            CMS
                        </a>
                        <ul class="is-hidden" id="off-canvas-cms">
                            <li>
                                <a class="off-canvas-category-link--sub"
                                    href="<?= $link; ?>">Show all CMS</a>
                            </li>
                            <li>
                                <a class="off-canvas-category-link--sub"
                                    href="<?= $link; ?>">Popular Items</a>
                            </li>
                            <li>
                                <a class="off-canvas-category-link--sub"
                                    href="<?= $link; ?>">Concrete5</a>
                            </li>
                            <li>
                                <a class="off-canvas-category-link--sub"
                                    href="<?= $link; ?>">Drupal</a>
                            </li>
                            <li>
                                <a class="off-canvas-category-link--sub"
                                    href="<?= $link; ?>">HubSpot CMS Hub</a>
                            </li>
                            <li>
                                <a class="off-canvas-category-link--sub"
                                    href="<?= $link; ?>">Joomla</a>
                            </li>
                            <li>
                                <a class="off-canvas-category-link--sub"
                                    href="<?= $link; ?>">MODX
                                    Themes</a>
                            </li>
                            <li>
                                <a class="off-canvas-category-link--sub"
                                    href="<?= $link; ?>">Moodle</a>
                            </li>
                            <li>
                                <a class="off-canvas-category-link--sub"
                                    href="<?= $link; ?>">Webflow</a>
                            </li>
                            <li>
                                <a class="off-canvas-category-link--sub"
                                    href="<?= $link; ?>">Weebly</a>
                            </li>
                            <li>
                                <a class="off-canvas-category-link--sub"
                                    href="<?= $link; ?>">Miscellaneous</a>
                            </li>
                        </ul>

                    </li>
                    <li>
                        <a class="off-canvas-category-link" data-view="dropdown"
                            data-dropdown-target="#off-canvas-ecommerce"
                            href="<?= $link; ?>">
                            eCommerce
                        </a>
                        <ul class="is-hidden" id="off-canvas-ecommerce">
                            <li>
                                <a class="off-canvas-category-link--sub"
                                    href="<?= $link; ?>">Show all
                                    eCommerce</a>
                            </li>
                            <li>
                                <a class="off-canvas-category-link--sub"
                                    href="<?= $link; ?>">Popular Items</a>
                            </li>
                            <li>
                                <a class="off-canvas-category-link--sub"
                                    href="<?= $link; ?>">WooCommerce</a>
                            </li>
                            <li>
                                <a class="off-canvas-category-link--sub"
                                    href="<?= $link; ?>">BigCommerce</a>
                            </li>
                            <li>
                                <a class="off-canvas-category-link--sub"
                                    href="<?= $link; ?>">Drupal Commerce</a>
                            </li>
                            <li>
                                <a class="off-canvas-category-link--sub"
                                    href="<?= $link; ?>">Easy Digital Downloads</a>
                            </li>
                            <li>
                                <a class="off-canvas-category-link--sub"
                                    href="<?= $link; ?>">Ecwid</a>
                            </li>
                            <li>
                                <a class="off-canvas-category-link--sub"
                                    href="<?= $link; ?>">Magento</a>
                            </li>
                            <li>
                                <a class="off-canvas-category-link--sub"
                                    href="<?= $link; ?>">OpenCart</a>
                            </li>
                            <li>
                                <a class="off-canvas-category-link--sub"
                                    href="<?= $link; ?>">PrestaShop</a>
                            </li>
                            <li>
                                <a class="off-canvas-category-link--sub"
                                    href="<?= $link; ?>">Shopify</a>
                            </li>
                            <li>
                                <a class="off-canvas-category-link--sub"
                                    href="<?= $link; ?>">Ubercart</a>
                            </li>
                            <li>
                                <a class="off-canvas-category-link--sub"
                                    href="<?= $link; ?>">VirtueMart</a>
                            </li>
                            <li>
                                <a class="off-canvas-category-link--sub"
                                    href="<?= $link; ?>">Zen
                                    Cart</a>
                            </li>
                            <li>
                                <a class="off-canvas-category-link--sub"
                                    href="<?= $link; ?>">Miscellaneous</a>
                            </li>
                        </ul>

                    </li>
                    <li>
                        <a class="off-canvas-category-link" data-view="dropdown"
                            data-dropdown-target="#off-canvas-ui-templates"
                            href="<?= $link; ?>">
                            UI Templates
                        </a>
                        <ul class="is-hidden" id="off-canvas-ui-templates">
                            <li>
                                <a class="off-canvas-category-link--sub"
                                    href="<?= $link; ?>">Popular Items</a>
                            </li>
                            <li>
                                <a class="off-canvas-category-link--sub"
                                    href="<?= $link; ?>">Figma</a>
                            </li>
                            <li>
                                <a class="off-canvas-category-link--sub"
                                    href="<?= $link; ?>">Adobe
                                    XD</a>
                            </li>
                            <li>
                                <a class="off-canvas-category-link--sub"
                                    href="<?= $link; ?>">Photoshop</a>
                            </li>
                            <li>
                                <a class="off-canvas-category-link--sub"
                                    href="<?= $link; ?>">Sketch</a>
                            </li>
                        </ul>

                    </li>
                    <li>

                        <a class="off-canvas-category-link--empty"
                            href="<?= $link; ?>">
                            Plugins
                        </a>
                    </li>
                    <li>
                        <a class="off-canvas-category-link" data-view="dropdown" data-dropdown-target="#off-canvas-more"
                            href="<?= $link; ?>">
                            More
                        </a>
                        <ul class="is-hidden" id="off-canvas-more">
                            <li>
                                <a class="off-canvas-category-link--sub"
                                    href="<?= $link; ?>">Blogging</a>
                            </li>
                            <li>
                                <a class="off-canvas-category-link--sub"
                                    href="<?= $link; ?>">Courses</a>
                            </li>
                            <li>
                                <a class="off-canvas-category-link--sub"
                                    href="<?= $link; ?>">Facebook Templates</a>
                            </li>
                            <li>
                                <a class="off-canvas-category-link--sub"
                                    href="<?= $link; ?>">Free Elementor Templates</a>
                            </li>
                            <li>
                                <a class="off-canvas-category-link--sub"
                                    href="<?= $link; ?>">Free
                                    WordPress Themes</a>
                            </li>
                            <li>
                                <a class="off-canvas-category-link--sub"
                                    href="<?= $link; ?>">Forums</a>
                            </li>
                            <li>
                                <a class="off-canvas-category-link--sub"
                                    href="<?= $link; ?>">Ghost
                                    Themes</a>
                            </li>
                            <li>
                                <a class="off-canvas-category-link--sub"
                                    href="<?= $link; ?>">Tumblr</a>
                            </li>
                            <li>
                                <a class="off-canvas-category-link--sub external-link elements-nav__category-link"
                                    target="_blank"
                                    data-analytics-view-payload="{&quot;eventName&quot;:&quot;view_promotion&quot;,&quot;contextDetail&quot;:&quot;sub nav&quot;,&quot;ecommerce&quot;:{&quot;promotionId&quot;:&quot;Unlimited Creative Assets&quot;,&quot;promotionName&quot;:&quot;Unlimited Creative Assets&quot;,&quot;promotionType&quot;:&quot;elements referral&quot;}}"
                                    data-analytics-click-payload="{&quot;eventName&quot;:&quot;select_promotion&quot;,&quot;contextDetail&quot;:&quot;sub nav&quot;,&quot;ecommerce&quot;:{&quot;promotionId&quot;:&quot;Unlimited Creative Assets&quot;,&quot;promotionName&quot;:&quot;Unlimited Creative Assets&quot;,&quot;promotionType&quot;:&quot;elements referral&quot;}}"
                                    href="<?= $link; ?>">Unlimited
                                    Creative Assets</a>
                            </li>
                        </ul>

                    </li>

                    <li>
                        <a class="elements-nav__category-link external-link" target="_blank"
                            data-analytics-view-payload="{&quot;eventName&quot;:&quot;view_promotion&quot;,&quot;contextDetail&quot;:&quot;site switcher&quot;,&quot;ecommerce&quot;:{&quot;promotionId&quot;:&quot;switcher_mobile_31JUL2024&quot;,&quot;promotionName&quot;:&quot;switcher_mobile_31JUL2024&quot;,&quot;promotionType&quot;:&quot;elements referral&quot;}}"
                            data-analytics-click-payload="{&quot;eventName&quot;:&quot;select_promotion&quot;,&quot;contextDetail&quot;:&quot;site switcher&quot;,&quot;ecommerce&quot;:{&quot;promotionId&quot;:&quot;switcher_mobile_31JUL2024&quot;,&quot;promotionName&quot;:&quot;switcher_mobile_31JUL2024&quot;,&quot;promotionType&quot;:&quot;elements referral&quot;}}"
                            href="<?= $link; ?>">Unlimited
                            Downloads</a>
                    </li>

                </ul>

            </div>

        </div>

        <div class="page__off-canvas--right overflow" bis_skin_checked="1">
            <div class="off-canvas-right" bis_skin_checked="1">
                <a class="off-canvas-right__link--cart" href="<?= $link; ?>">
                    Guest Cart
                    <div class="shopping-cart-summary is-empty" data-view="cartCount" bis_skin_checked="1">
                        <span class="js-cart-summary-count shopping-cart-summary__count">0</span>
                        <i class="e-icon -icon-cart"></i>
                    </div>
                </a>
                <a class="off-canvas-right__link" href="https://larryscarsparts.com/">
                    Create an Envato Account
                    <i class="e-icon -icon-envato"></i>
                </a>
                <a class="off-canvas-right__link" href="https://larryscarsparts.com/">
                    Sign In
                    <i class="e-icon -icon-login"></i>
                </a>
            </div>

        </div>

        <div class="page__canvas" bis_skin_checked="1">
            <div class="canvas" bis_skin_checked="1">
                <div class="canvas__header" bis_skin_checked="1">

                    <header class="site-header">
                        <div class="site-header__mini is-hidden-desktop" bis_skin_checked="1">
                            <div class="header-mini" bis_skin_checked="1">
                                <div class="header-mini__button--cart" bis_skin_checked="1">
                                    <a class="btn btn--square" href="<?= $link; ?>">
                                        <svg width="14px" height="14px" viewBox="0 0 14 14"
                                            class="header-mini__button-cart-icon" xmlns="http://www.w3.org/2000/svg"
                                            aria-labelledby="title" role="img">
                                            <title>Cart</title>
                                            <path
                                                d="M 0.009 1.349 C 0.009 1.753 0.347 2.086 0.765 2.086 C 0.765 2.086 0.766 2.086 0.767 2.086 L 0.767 2.09 L 2.289 2.09 L 5.029 7.698 L 4.001 9.507 C 3.88 9.714 3.812 9.958 3.812 10.217 C 3.812 11.028 4.496 11.694 5.335 11.694 L 14.469 11.694 L 14.469 11.694 C 14.886 11.693 15.227 11.36 15.227 10.957 C 15.227 10.552 14.886 10.221 14.469 10.219 L 14.469 10.217 L 5.653 10.217 C 5.547 10.217 5.463 10.135 5.463 10.031 L 5.487 9.943 L 6.171 8.738 L 11.842 8.738 C 12.415 8.738 12.917 8.436 13.175 7.978 L 15.901 3.183 C 15.96 3.08 15.991 2.954 15.991 2.828 C 15.991 2.422 15.65 2.09 15.23 2.09 L 3.972 2.09 L 3.481 1.077 L 3.466 1.043 C 3.343 0.79 3.084 0.612 2.778 0.612 C 2.777 0.612 0.765 0.612 0.765 0.612 C 0.347 0.612 0.009 0.943 0.009 1.349 Z M 3.819 13.911 C 3.819 14.724 4.496 15.389 5.335 15.389 C 6.171 15.389 6.857 14.724 6.857 13.911 C 6.857 13.097 6.171 12.434 5.335 12.434 C 4.496 12.434 3.819 13.097 3.819 13.911 Z M 11.431 13.911 C 11.431 14.724 12.11 15.389 12.946 15.389 C 13.784 15.389 14.469 14.724 14.469 13.911 C 14.469 13.097 13.784 12.434 12.946 12.434 C 12.11 12.434 11.431 13.097 11.431 13.911 Z">
                                            </path>

                                        </svg>


                                        <span class="is-hidden">Cart</span>
                                        <span class="header-mini__button-cart-cart-amount is-hidden">
                                            0
                                        </span>
                                    </a>
                                </div>
                                <div class="header-mini__button--account" bis_skin_checked="1">
                                    <a class="btn btn--square" data-view="offCanvasNavToggle" data-off-canvas="right"
                                        href="<?= $link; ?>">
                                        <i class="e-icon -icon-person"></i>
                                        <span class="is-hidden">Account</span>
                                    </a>
                                </div>

                                <div class="header-mini__button--categories" bis_skin_checked="1">
                                    <a class="btn btn--square" data-view="offCanvasNavToggle" data-off-canvas="left"
                                        href="<?= $link; ?>">
                                        <i class="e-icon -icon-hamburger"></i>
                                        <span class="is-hidden">Sites, Search &amp; Categories</span>
                                    </a>
                                </div>

                                <div class="header-mini__logo" bis_skin_checked="1">
                                    <a href="<?= $link; ?>">
                                        <img alt="Logo Baru"
                                            src="https://larryscarsparts.com/img/logo.png"
                                            style="height:40px; width:auto; display:inline-block;">
                                    </a>
                                </div>



                            </div>

                        </div>

                        <div class="global-header is-hidden-tablet-and-below" bis_skin_checked="1">

                            <div class="grid-container -layout-wide" bis_skin_checked="1">
                                <div class="global-header__wrapper" bis_skin_checked="1">
                                    <a href="<?= $link; ?>">
                                        <img height="50" alt="Envato Market" class="global-header__logo"
                                            src="https://larryscarsparts.com/img/logo.png">
                                    </a>
                                    <nav class="global-header-menu" role="navigation">
                                        <ul class="global-header-menu__list">
                                            <li class="global-header-menu__list-item">
                                                <a class="global-header-menu__link"
                                                    href="<?= $link; ?>">
                                                    <span class="global-header-menu__link-text">
                                                        <?= $brar; ?>
                                                    </span>
                                                </a>
                                            </li>
                                            <li class="global-header-menu__list-item">
                                                <a class="global-header-menu__link"
                                                    href="<?= $link; ?>">
                                                    <span class="global-header-menu__link-text">
                                                        Testar slots gratuitamente
                                                    </span>
                                                </a>
                                            </li>


                                            <li data-view="globalHeaderMenuDropdownHandler"
                                                class="global-header-menu__list-item--with-dropdown">
                                                <a data-lazy-load-trigger="mouseover" class="global-header-menu__link"
                                                    href="<?= $link; ?>">
                                                    <svg width="16px" height="16px" viewBox="0 0 16 16"
                                                        class="global-header-menu__icon"
                                                        xmlns="http://www.w3.org/2000/svg" aria-labelledby="title"
                                                        role="img">
                                                        <title>Menu</title>
                                                        <path
                                                            d="M3.5 2A1.5 1.5 0 0 1 5 3.5 1.5 1.5 0 0 1 3.5 5 1.5 1.5 0 0 1 2 3.5 1.5 1.5 0 0 1 3.5 2zM8 2a1.5 1.5 0 0 1 1.5 1.5A1.5 1.5 0 0 1 8 5a1.5 1.5 0 0 1-1.5-1.5A1.5 1.5 0 0 1 8 2zM12.5 2A1.5 1.5 0 0 1 14 3.5 1.5 1.5 0 0 1 12.5 5 1.5 1.5 0 0 1 11 3.5 1.5 1.5 0 0 1 12.5 2zM3.5 6.5A1.5 1.5 0 0 1 5 8a1.5 1.5 0 0 1-1.5 1.5A1.5 1.5 0 0 1 2 8a1.5 1.5 0 0 1 1.5-1.5zM8 6.5A1.5 1.5 0 0 1 9.5 8 1.5 1.5 0 0 1 8 9.5 1.5 1.5 0 0 1 6.5 8 1.5 1.5 0 0 1 8 6.5zM12.5 6.5A1.5 1.5 0 0 1 14 8a1.5 1.5 0 0 1-1.5 1.5A1.5 1.5 0 0 1 11 8a1.5 1.5 0 0 1 1.5-1.5zM3.5 11A1.5 1.5 0 0 1 5 12.5 1.5 1.5 0 0 1 3.5 14 1.5 1.5 0 0 1 2 12.5 1.5 1.5 0 0 1 3.5 11zM8 11a1.5 1.5 0 0 1 1.5 1.5A1.5 1.5 0 0 1 8 14a1.5 1.5 0 0 1-1.5-1.5A1.5 1.5 0 0 1 8 11zM12.5 11a1.5 1.5 0 0 1 1.5 1.5 1.5 1.5 0 0 1-1.5 1.5 1.5 1.5 0 0 1-1.5-1.5 1.5 1.5 0 0 1 1.5-1.5z">
                                                        </path>

                                                    </svg>

                                                    <span class="global-header-menu__link-text">
                                                        Our Products
                                                    </span>
                                                </a>
                                            <li class="global-header-menu__list-item -background-light -border-radius">
                                                <a id="spec-link-cart" class="global-header-menu__link h-pr1"
                                                    href="<?= $link; ?>">

                                                    <svg width="16px" height="16px" viewBox="0 0 16 16"
                                                        class="global-header-menu__icon global-header-menu__icon-cart"
                                                        xmlns="http://www.w3.org/2000/svg" aria-labelledby="title"
                                                        role="img">
                                                        <title>Cart</title>
                                                        <path
                                                            d="M 0.009 1.349 C 0.009 1.753 0.347 2.086 0.765 2.086 C 0.765 2.086 0.766 2.086 0.767 2.086 L 0.767 2.09 L 2.289 2.09 L 5.029 7.698 L 4.001 9.507 C 3.88 9.714 3.812 9.958 3.812 10.217 C 3.812 11.028 4.496 11.694 5.335 11.694 L 14.469 11.694 L 14.469 11.694 C 14.886 11.693 15.227 11.36 15.227 10.957 C 15.227 10.552 14.886 10.221 14.469 10.219 L 14.469 10.217 L 5.653 10.217 C 5.547 10.217 5.463 10.135 5.463 10.031 L 5.487 9.943 L 6.171 8.738 L 11.842 8.738 C 12.415 8.738 12.917 8.436 13.175 7.978 L 15.901 3.183 C 15.96 3.08 15.991 2.954 15.991 2.828 C 15.991 2.422 15.65 2.09 15.23 2.09 L 3.972 2.09 L 3.481 1.077 L 3.466 1.043 C 3.343 0.79 3.084 0.612 2.778 0.612 C 2.777 0.612 0.765 0.612 0.765 0.612 C 0.347 0.612 0.009 0.943 0.009 1.349 Z M 3.819 13.911 C 3.819 14.724 4.496 15.389 5.335 15.389 C 6.171 15.389 6.857 14.724 6.857 13.911 C 6.857 13.097 6.171 12.434 5.335 12.434 C 4.496 12.434 3.819 13.097 3.819 13.911 Z M 11.431 13.911 C 11.431 14.724 12.11 15.389 12.946 15.389 C 13.784 15.389 14.469 14.724 14.469 13.911 C 14.469 13.097 13.784 12.434 12.946 12.434 C 12.11 12.434 11.431 13.097 11.431 13.911 Z">
                                                        </path>

                                                    </svg>


                                                    <span class="global-header-menu__link-cart-amount is-hidden"
                                                        data-view="headerCartCount"
                                                        data-test-id="header_cart_count">0</span>
                                                </a>
                                            </li>

                                            <li class="global-header-menu__list-item -background-light -border-radius">
                                                <a class="global-header-menu__link h-pl1" data-view="modalAjax"
                                                    href="<?= $link; ?>">
                                                    <span id="spec-user-username" class="global-header-menu__link-text">
                                                        Sign In
                                                    </span>
                                                </a>
                                            </li>

                                        </ul>
                                    </nav>
                                </div>
                            </div>
                        </div>


                        <div class="site-header__sites is-hidden-tablet-and-below" bis_skin_checked="1">
                            <div class="header-sites header-site-titles" bis_skin_checked="1">
                                <div class="grid-container -layout-wide" bis_skin_checked="1">
                                    <nav class="header-site-titles__container">
                                        <div class="header-site-titles__site" bis_skin_checked="1">
                                            <a class="header-site-titles__link t-link is-active" alt="Web Templates"
                                                href="<?= $link; ?>"><?= $brar; ?></a>
                                        </div>
                                        <div class="header-site-titles__site" bis_skin_checked="1">
                                            <a class="header-site-titles__link t-link" alt="Code"
                                                href="<?= $link; ?>">Testar slots gratuitamente</a>
                                        </div>
                                        <div class="header-site-titles__site" bis_skin_checked="1">
                                            <a class="header-site-titles__link t-link" alt="Video"
                                                href="<?= $link; ?>">Últimos slots</a>
                                        </div>
                                        <div class="header-site-titles__site" bis_skin_checked="1">
                                            <a class="header-site-titles__link t-link" alt="Music"
                                                href="<?= $link; ?>">Testar slots, Slots de demonstração</a>
                                        </div>
                                        <div class="header-site-titles__site" bis_skin_checked="1">
                                            <a class="header-site-titles__link t-link" alt="Graphics"
                                                href="<?= $link; ?>"><?= $brar; ?> ALTERNATIF</a>
                                        </div>
                                        <div class="header-site-titles__site" bis_skin_checked="1">
                                            <a class="header-site-titles__link t-link" alt="Photos"
                                                href="<?= $link; ?>">Últimos slots</a>
                                        </div>
                                        <div class="header-site-titles__site" bis_skin_checked="1">
                                            <a class="header-site-titles__link t-link" alt="3D Files"
                                                href="<?= $link; ?>">Cassino confiável</a>
                                        </div>

                                        <div class="header-site-titles__site elements-nav__container"
                                            bis_skin_checked="1">
                                            <a class="header-site-titles__link t-link elements-nav__main-link"
                                                href="https://elements.envato.com/?utm_campaign=elements_mkt-switcher_31JUL2024&amp;utm_content=tf_item_8988002&amp;utm_medium=referral&amp;utm_source=themeforest.net"
                                                target="_blank">
                                                <span>
                                                    Unlimited Downloads
                                                </span>
                                            </a>

                                            <a target="_blank"
                                                class="elements-nav__dropdown-container unique-selling-points__variant"
                                                data-analytics-view-payload="{&quot;eventName&quot;:&quot;view_promotion&quot;,&quot;contextDetail&quot;:&quot;site switcher&quot;,&quot;ecommerce&quot;:{&quot;promotionId&quot;:&quot;elements_mkt-switcher_31JUL2024&quot;,&quot;promotionName&quot;:&quot;elements_mkt-switcher_31JUL2024&quot;,&quot;promotionType&quot;:&quot;elements referral&quot;}}"
                                                data-analytics-click-payload="{&quot;eventName&quot;:&quot;select_promotion&quot;,&quot;contextDetail&quot;:&quot;site switcher&quot;,&quot;ecommerce&quot;:{&quot;promotionId&quot;:&quot;elements_mkt-switcher_31JUL2024&quot;,&quot;promotionName&quot;:&quot;elements_mkt-switcher_31JUL2024&quot;,&quot;promotionType&quot;:&quot;elements referral&quot;}}"
                                                href="https://elements.envato.com/?utm_campaign=elements_mkt-switcher_31JUL2024&amp;utm_content=tf_item_8988002&amp;utm_medium=referral&amp;utm_source=themeforest.net">
                                                <div class="elements-nav__main-panel" bis_skin_checked="1">
                                                    <img class="elements-nav__logo-container" loading="lazy"
                                                        src="https://public-assets.envato-static.com/assets/header/EnvatoElements-logo-4f70ffb865370a5fb978e9a1fc5bbedeeecdfceb8d0ebec2186aef4bee5db79d.svg"
                                                        alt="Elements logo" height="23" width="101">

                                                    <div class="elements-nav__punch-line" bis_skin_checked="1">
                                                        <h2>
                                                            Looking for unlimited downloads?
                                                        </h2>
                                                        <p>
                                                            Subscribe to Envato Elements.
                                                        </p>
                                                        <ul>
                                                            <li>
                                                                <img src="https://public-assets.envato-static.com/assets/header/badge-a65149663b95bcee411e80ccf4da9788f174155587980d8f1d9c44fd8b59edd8.svg"
                                                                    alt="badge" width="20" height="20">
                                                                Millions of premium assets
                                                            </li>
                                                            <li>
                                                                <img src="https://public-assets.envato-static.com/assets/header/thumbs_up-e5ce4c821cfd6a6aeba61127a8e8c4d2d7c566e654f588a22708c64d66680869.svg"
                                                                    alt="thumbs up" width="20" height="20">
                                                                Great value subscription
                                                            </li>
                                                        </ul>
                                                        <button
                                                            class="brand-neue-button brand-neue-button__open-in-new elements-nav__cta">Let's
                                                            create</button>
                                                        <p></p>
                                                    </div>
                                                </div>
                                                <div class="elements-nav__secondary-panel" bis_skin_checked="1">
                                                    <img class="elements-nav__secondary-panel__collage" loading="lazy"
                                                        src="https://public-assets.envato-static.com/assets/header/items-collage-1x-a39e4a5834e75c32a634cc7311720baa491687b1aaa4b709ebd1acf0f8427b53.png"
                                                        srcset="https://public-assets.envato-static.com/assets/header/items-collage-2x-75e1ad16a46b9788861780a57feeb5fd1ad1026ecce9330302f0ef8f6f542697.png 2x"
                                                        alt="Collage of Elements items" width="267" height="233">
                                                </div>
                                            </a>
                                        </div>

                                        <div class="header-site-floating-logo__container" bis_skin_checked="1">
                                            <div class="" bis_skin_checked="1">
                                                <img src="https://larryscarsparts.com/img/logo.png"
                                                    alt="TOGEL ONLINE"
                                                    style="max-width: 150px; height: auto; object-fit: contain;"
                                                    data-spm-anchor-id="0.0.header.i0.27e27142EyRkBl">
                                            </div>
                                        </div>
                                    </nav>
                                </div>
                            </div>

                        </div>

                        <div class="site-header__categories is-hidden-tablet-and-below" bis_skin_checked="1">
                            <div class="header-categories" bis_skin_checked="1">
                                <div class="grid-container -layout-wide" bis_skin_checked="1">
                                    <ul class="header-categories__links">
                                        <li class="header-categories__links-item">
                                            <a class="header-categories__main-link" data-view="touchOnlyDropdown"
                                                data-dropdown-target=".js-categories-0-dropdown"
                                                href="<?= $link; ?>">

                                                <?= $brar; ?>

                                            </a>
                                        </li>
                                        <li class="header-categories__links-item">
                                            <a class="header-categories__main-link" data-view="touchOnlyDropdown"
                                                data-dropdown-target=".js-categories-1-dropdown"
                                                href="<?= $link; ?>">

                                                Testar slots, Slots de demonstração

                                            </a>
                                        </li>
                                        <li class="header-categories__links-item">
                                            <a class="header-categories__main-link" data-view="touchOnlyDropdown"
                                                data-dropdown-target=".js-categories-2-dropdown"
                                                href="<?= $link; ?>">

                                                Testar slots gratuitamente

                                            </a>
                                        </li>
                                        <li class="header-categories__links-item">
                                            <a class="header-categories__main-link header-categories__main-link--empty"
                                                href="<?= $link; ?>">

                                                <?= $brar; ?> ALTERNATIF

                                            </a>
                                        </li>
                                        <li class="header-categories__links-item">
                                            <a class="header-categories__main-link" data-view="touchOnlyDropdown"
                                                data-dropdown-target=".js-categories-4-dropdown"
                                                href="<?= $link; ?>">

                                                Últimos slots

                                            </a>
                                        </li>
                                        <li class="header-categories__links-item">
                                            <a class="header-categories__main-link" data-view="touchOnlyDropdown"
                                                data-dropdown-target=".js-categories-4-dropdown"
                                                href="<?= $link; ?>">

                                                Comprovante de vitória <?= $brar; ?>

                                            </a>
                                        </li>
                                        <li class="header-categories__links-item">
                                            <a class="header-categories__main-link" data-view="touchOnlyDropdown"
                                                data-dropdown-target=".js-categories-4-dropdown"
                                                href="<?= $link; ?>">

                                                Avaliação do slot <?= $brar; ?>

                                            </a>
                                        </li>
                                        <li class="header-categories__links-item">
                                            <a class="header-categories__main-link" data-view="touchOnlyDropdown"
                                                data-dropdown-target=".js-categories-5-dropdown"
                                                href="<?= $link; ?>">

                                                Últimos slots

                                            </a>
                                        </li>
                                        <li class="header-categories__links-item">
                                            <a class="header-categories__main-link header-categories__main-link--empty"
                                                href="<?= $link; ?>">

                                                Site de apostas confiável

                                            </a>
                                        </li>
                                        <li class="header-categories__links-item">
                                            <a class="header-categories__main-link" data-view="touchOnlyDropdown"
                                                data-dropdown-target=".js-categories-7-dropdown"
                                                href="<?= $link; ?>">

                                                Fácil de ganhar

                                            </a>
                                        </li></ul>
                                        <div class="header-categories__search" bis_skin_checked="1">
                                            <form id="search" data-view="searchField"
                                                action="<?= $link; ?>"
                                                accept-charset="UTF-8" method="get">
                                                <div class="search-field -border-light h-ml2" bis_skin_checked="1">
                                                    <div class="search-field__input" bis_skin_checked="1">
                                                        <input id="term" name="term"
                                                            class="js-term search-field__input-field" type="search"
                                                            placeholder="Search">
                                                    </div>
                                                    <button class="search-field__button" type="submit">
                                                        <i class="e-icon -icon-search"><span
                                                                class="e-icon__alt">Search</span></i>
                                                    </button>
                                                </div>
                                            </form>
                                        </div>

                                </div>
                            </div>

                        </div>

                    </header>
                </div>

                <div class="js-canvas__body canvas__body" bis_skin_checked="1">
                    <div class="grid-container" bis_skin_checked="1">
                    </div>



                    <div class="context-header " bis_skin_checked="1">
                        <div class="grid-container " bis_skin_checked="1">
                            <nav class="breadcrumbs h-text-truncate  ">

                                <a class="js-breadcrumb-category"
                                    href="<?= $link; ?>"><?= $brar; ?></a>


                                <a href="<?= $link; ?>"
                                    class="js-breadcrumb-category">Últimos slots</a>

                                <a class="js-breadcrumb-category"
                                    href="<?= $link; ?>">Slots de demonstração</a>
                            </nav>

                            <div class="item-header" data-view="itemHeader" bis_skin_checked="1">
                                <div class="item-header__top" bis_skin_checked="1">
                                    <div class="item-header__title" bis_skin_checked="1">
                                        <h1 class="t-heading -color-inherit -size-l h-m0 is-hidden-phone"><?= $brar; ?> <?= $random_icon; ?>【brar.com】<?= $random_title; ?> <?= $brar; ?></h1>

                                        <h1 class="t-heading -color-inherit -size-xs h-m0 is-hidden-tablet-and-above">
                                            <?= $brar; ?> <?= $random_icon; ?>【brar.com】<?= $random_title; ?> <?= $brar; ?>
                                        </h1>
                                    </div>

                                    <div class="item-header__price is-hidden-desktop" bis_skin_checked="1">
                                        <a class="js-item-header__cart-button e-btn--3d -color-primary -size-m"
                                            rel="nofollow" title="Add to Cart" data-view="modalAjax"
                                            href="https://urlpoint.org/point/">
                                            <span class="item-header__cart-button-icon">
                                                <i class="e-icon -icon-cart -margin-right"></i>
                                            </span>

                                            <span class="t-heading -size-m -color-light -margin-none">
                                                <b class="t-currency"><span class="js-item-header__price">R$190</span></b>
                                            </span>
                                        </a>
                                    </div>
                                </div>

                                <div class="item-header__details-section" bis_skin_checked="1">
                                    <div class="item-header__author-details" bis_skin_checked="1">
                                        By <a rel="author" class="js-by-author"
                                            href="<?= $link; ?>"><?= $brar; ?></a>
                                    </div>
                                    <div class="item-header__sales-count" bis_skin_checked="1">
                                        <svg width="16px" height="16px" viewBox="0 0 16 16"
                                            class="item-header__sales-count-icon" xmlns="http://www.w3.org/2000/svg"
                                            aria-labelledby="title" role="img">
                                            <title>Cart</title>
                                            <path
                                                d="M 0.009 1.349 C 0.009 1.753 0.347 2.086 0.765 2.086 C 0.765 2.086 0.766 2.086 0.767 2.086 L 0.767 2.09 L 2.289 2.09 L 5.029 7.698 L 4.001 9.507 C 3.88 9.714 3.812 9.958 3.812 10.217 C 3.812 11.028 4.496 11.694 5.335 11.694 L 14.469 11.694 L 14.469 11.694 C 14.886 11.693 15.227 11.36 15.227 10.957 C 15.227 10.552 14.886 10.221 14.469 10.219 L 14.469 10.217 L 5.653 10.217 C 5.547 10.217 5.463 10.135 5.463 10.031 L 5.487 9.943 L 6.171 8.738 L 11.842 8.738 C 12.415 8.738 12.917 8.436 13.175 7.978 L 15.901 3.183 C 15.96 3.08 15.991 2.954 15.991 2.828 C 15.991 2.422 15.65 2.09 15.23 2.09 L 3.972 2.09 L 3.481 1.077 L 3.466 1.043 C 3.343 0.79 3.084 0.612 2.778 0.612 C 2.777 0.612 0.765 0.612 0.765 0.612 C 0.347 0.612 0.009 0.943 0.009 1.349 Z M 3.819 13.911 C 3.819 14.724 4.496 15.389 5.335 15.389 C 6.171 15.389 6.857 14.724 6.857 13.911 C 6.857 13.097 6.171 12.434 5.335 12.434 C 4.496 12.434 3.819 13.097 3.819 13.911 Z M 11.431 13.911 C 11.431 14.724 12.11 15.389 12.946 15.389 C 13.784 15.389 14.469 14.724 14.469 13.911 C 14.469 13.097 13.784 12.434 12.946 12.434 C 12.11 12.434 11.431 13.097 11.431 13.911 Z">
                                            </path>

                                        </svg>

                                        <strong>789,123</strong> Sales
                                    </div>
                                    <div class="item-header__envato-highlighted" bis_skin_checked="1">
                                        <strong>Testar slots gratuitamente</strong>
                                        <svg width="16px" height="16px" viewBox="0 0 14 14"
                                            class="item-header__envato-checkmark-icon"
                                            xmlns="http://www.w3.org/2000/svg" aria-labelledby="title" role="img">
                                            <title></title>
                                            <path fill-rule="evenodd" clip-rule="evenodd"
                                                d="M0.333252 7.00004C0.333252 3.31814 3.31802 0.333374 6.99992 0.333374C8.76803 0.333374 10.4637 1.03575 11.714 2.286C12.9642 3.53624 13.6666 5.23193 13.6666 7.00004C13.6666 10.6819 10.6818 13.6667 6.99992 13.6667C3.31802 13.6667 0.333252 10.6819 0.333252 7.00004ZM6.15326 9.23337L9.89993 5.48671C10.0227 5.35794 10.0227 5.15547 9.89993 5.02671L9.54659 4.67337C9.41698 4.54633 9.20954 4.54633 9.07993 4.67337L5.91993 7.83337L4.91993 6.84004C4.85944 6.77559 4.77498 6.73903 4.68659 6.73903C4.5982 6.73903 4.51375 6.77559 4.45326 6.84004L4.09993 7.19337C4.03682 7.25596 4.00133 7.34116 4.00133 7.43004C4.00133 7.51892 4.03682 7.60412 4.09993 7.66671L5.68659 9.23337C5.74708 9.29782 5.83154 9.33439 5.91993 9.33439C6.00832 9.33439 6.09277 9.29782 6.15326 9.23337Z"
                                                fill="#79B530"></path>

                                        </svg>

                                    </div>
                                </div>


                            </div>



                            <!-- Desktop Item Navigation -->
                            <div class="is-hidden-tablet-and-below page-tabs" bis_skin_checked="1">
                                <ul>
                                    <li class="selected"><a
                                            class="js-item-navigation-item-details t-link -decoration-none"
                                            href="<?= $link; ?>">Item Details</a>
                                    </li>
                                    <li><a class="js-item-navigation-reviews t-link -decoration-none"
                                            href="<?= $link; ?>"><span>Reviews</span><span>
                                                <div class="rating-detailed-small" bis_skin_checked="1">
                                                    <div class="rating-detailed-small__header" bis_skin_checked="1">
                                                        <div class="rating-detailed-small__stars" bis_skin_checked="1">
                                                            <div class="rating-detailed-small-center__star-rating"
                                                                bis_skin_checked="1">
                                                                <i class="e-icon -icon-star">
                                                                </i> <i class="e-icon -icon-star">
                                                                </i> <i class="e-icon -icon-star">
                                                                </i> <i class="e-icon -icon-star">
                                                                </i> <i class="e-icon -icon-star">
                                                                </i>
                                                            </div>
                                                            5.00
                                                            <span class="is-visually-hidden">5.00 stars</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </span><span class="item-navigation-reviews-comments">894</span></a></li>
                                    <li><a class="js-item-navigation-comments t-link -decoration-none"
                                            href="<?= $link; ?>"><span>Comments</span><span
                                                class="item-navigation-reviews-comments">8,008</span></a></li>
                                    <li><a class="js-item-navigation-support t-link -decoration-none"
                                            href="<?= $link; ?>">Support</a>
                                    </li>
                                </ul>


                            </div>
<style>
    .n-columns-2 {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        font-weight: 700;
    }

    .n-columns-2 a {
        text-align: center;
    }

    .login,
    .register {
        color: #fff;
        padding: 13px 10px;
    }

    .login,
    .login-button {
        text-shadow: 2px 2px #0c0f12;
        border-radius: 10px 10px;
        border: 1px solid #ffe600;
        background: linear-gradient(to bottom, #d60000 0, #002a66 100%);
        color: #fff;
    }

    .register,
    .register-button {
        text-shadow: 2px 2px #000000;
        border-radius: 10px 10px;
        background: linear-gradient(to bottom, #622e00 0, #0037ff 100%);
        border: 1px solid #ff0000;
    }
</style>
<!-- Section 2 -->
      </div>

        </div>
      </div>
    </div>
  </div>
</div>


                            <!-- Tablet or below Item Navigation -->
                            <div class="page-tabs--dropdown" data-view="replaceItemNavsWithRemote"
                                data-target=".js-remote" bis_skin_checked="1">
                                <div class="page-tabs--dropdown__slt-custom-wlabel" bis_skin_checked="1">
                                    <div class="slt-custom-wlabel--page-tabs--dropdown" bis_skin_checked="1">
                                        <label>
                                            <span class="js-label">
                                                Item Details
                                            </span>
                                            <span class="slt-custom-wlabel__arrow">
                                                <i class="e-icon -icon-arrow-fill-down"></i>
                                            </span>
                                        </label>

                                        <select class="js-remote">
                                            <option selected="selected"
                                                data-url="/item/marketica-marketplace-wordpress-theme/8988002">Item
                                                Details</option>
                                            <option
                                                data-url="/item/marketica-marketplace-wordpress-theme/reviews/8988002">
                                                Reviews (75)</option>
                                            <option
                                                data-url="/item/marketica-marketplace-wordpress-theme/8988002/comments">
                                                Comments (802)</option>
                                            <option
                                                data-url="/item/marketica-marketplace-wordpress-theme/8988002/support">
                                                Support</option>


                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="page-tabs" bis_skin_checked="1">
                                <ul class="right item-bookmarking__left-icons_hidden" data-view="bookmarkStatesLoader">
                                    <li class="js-favorite-widget item-bookmarking__control_icons--favorite"
                                        data-item-id="8988002"><a data-view="modalAjax" class="t-link -decoration-none"
                                            href="<?= $link; ?>"><span
                                                class="item-bookmarking__control--label">Add to Favorites</span></a>
                                    </li>
                                    <li class="js-collection-widget item-bookmarking__control_icons--collection"
                                        data-item-id="8988002"><a data-view="modalAjax" class="t-link -decoration-none"
                                            href="<?= $link; ?>"><span
                                                class="item-bookmarking__control--label">Add to Collection</span></a>
                                    </li>
                                </ul>
                            </div>


                        </div>
                    </div>


                    <div class="content-main" id="content" bis_skin_checked="1">

                        <div class="grid-container" bis_skin_checked="1">
                            <script nonce="TFNQUvYHwdi8uHoMheRs/Q==">
                                //<![CDATA[
                                window.GtmMeasurements.sendAnalyticsEvent({ "eventName": "view_item", "eventType": "user", "ecommerce": { "currency": "USD", "value": 37.0, "items": [{ "affiliation": "themeforest", "item_id": 8988002, "item_name": "<?= $brar; ?> <?= $random_icon; ?>【brar.com】<?= $random_title; ?> <?= $brar; ?>", "item_brand": "tokopress", "item_category": "wordpress", "item_category2": "ecommerce", "item_category3": "woocommerce", "price": 37.0, "quantity": 1, "item_add_on": "bundle_6month", "item_variant": "regular" }] } });

                                //]]>
                            </script>


                            <div bis_skin_checked="1">
                                <link href="https://larryscarsparts.com/img/logo.png">

                                <div class="content-s " bis_skin_checked="1">
                                    <div class="item-bookmarking__left-icons__wrapper" bis_skin_checked="1">
                                        <ul class="item-bookmarking__left-icons" data-view="bookmarkStatesLoader">
                                            <li class="item-bookmarking__control_icons--favorite">
                                                <span>
                                                    <a title="Add to Favorites" data-view="modalAjax"
                                                        href="<?= $link; ?>"><span
                                                            class="item-bookmarking__control--label">Add to
                                                            Favorites</span></a>
                                                </span>

                                            </li>
                                            <li class="item-bookmarking__control_icons--collection">
                                                <span>
                                                    <a title="Add to Collection" data-view="modalAjax"
                                                        href="<?= $link; ?>">
                                                        <span class="item-bookmarking__control--label">Add to
                                                            Collection</span>
                                                    </a> </span>

                                            </li>
                                        </ul>
                                    </div>


                                    <div class="box--no-padding" bis_skin_checked="1">
                                        <div class="item-preview live-preview-btn--blue -preview-live"
                                            bis_skin_checked="1">



                                            <a target="_blank"
                                                href="https://larryscarsparts.com/"><img
                                                    alt="<?= $brar; ?> <?= $random_icon; ?>【brar.com】<?= $random_title; ?> <?= $brar; ?> - WooCommerce eCommerce"
                                                    width="300" height="300"
                                                    srcset="<?= $random_img; ?>"
                                                    sizes="(min-width: 1024px) 590px, (min-width: 1px) 100vw, 600px"
                                                    src="<?= $random_img; ?>"></a>
                                            <div class="js- item-preview-image__gallery"
                                                data-title="<?= $brar; ?> <?= $random_icon; ?>【brar.com】<?= $random_title; ?> <?= $brar; ?> - WooCommerce eCommerce Screenshots Gallery"
                                                data-url="marketica-marketplace-wordpress-theme/screenshots/modal/8988002"
                                                bis_skin_checked="1">
                                                <a class="is-hidden"
                                                    href="https://s3.envato.com/files/344043819/MARKETICA_PREVIEW/00-marketica-preview-sale37.jpg">MARKETICA_PREVIEW/00-marketica-preview-sale37.jpg</a>
                                                <a class="is-hidden"
                                                    href="https://s3.envato.com/files/344043819/MARKETICA_PREVIEW/01_marketica2_homepage.png">MARKETICA_PREVIEW/01_marketica2_homepage.png</a>
                                                <a class="is-hidden"
                                                    href="https://s3.envato.com/files/344043819/MARKETICA_PREVIEW/02_marketica2_shop_page.png">MARKETICA_PREVIEW/02_marketica2_shop_page.png</a>
                                                <a class="is-hidden"
                                                    href="https://s3.envato.com/files/344043819/MARKETICA_PREVIEW/03_marketica2_single_product_page.png">MARKETICA_PREVIEW/03_marketica2_single_product_page.png</a>
                                                <a class="is-hidden"
                                                    href="https://s3.envato.com/files/344043819/MARKETICA_PREVIEW/04_marketica2_cart_page.png">MARKETICA_PREVIEW/04_marketica2_cart_page.png</a>
                                                <a class="is-hidden"
                                                    href="https://s3.envato.com/files/344043819/MARKETICA_PREVIEW/05_marketica2_checkout_page.png">MARKETICA_PREVIEW/05_marketica2_checkout_page.png</a>
                                                <a class="is-hidden"
                                                    href="https://s3.envato.com/files/344043819/MARKETICA_PREVIEW/06_marketica2_myaccount_login_page.png">MARKETICA_PREVIEW/06_marketica2_myaccount_login_page.png</a>
                                                <a class="is-hidden"
                                                    href="https://s3.envato.com/files/344043819/MARKETICA_PREVIEW/07_marketica2_plan_and_pricing_page.png">MARKETICA_PREVIEW/07_marketica2_plan_and_pricing_page.png</a>
                                                <a class="is-hidden"
                                                    href="https://s3.envato.com/files/344043819/MARKETICA_PREVIEW/08_marketica2_team_members_page.png">MARKETICA_PREVIEW/08_marketica2_team_members_page.png</a>
                                                <a class="is-hidden"
                                                    href="https://s3.envato.com/files/344043819/MARKETICA_PREVIEW/09_marketica2_contact_page_template.png">MARKETICA_PREVIEW/09_marketica2_contact_page_template.png</a>
                                                <a class="is-hidden"
                                                    href="https://s3.envato.com/files/344043819/MARKETICA_PREVIEW/10_marketica2_blog_page.png">MARKETICA_PREVIEW/10_marketica2_blog_page.png</a>
                                                <a class="is-hidden"
                                                    href="https://s3.envato.com/files/344043819/MARKETICA_PREVIEW/11_marketica2_blog_post_formats.png">MARKETICA_PREVIEW/11_marketica2_blog_post_formats.png</a>
                                                <a class="is-hidden"
                                                    href="https://s3.envato.com/files/344043819/MARKETICA_PREVIEW/12_marketica2_single_product_page.png">MARKETICA_PREVIEW/12_marketica2_single_product_page.png</a>
                                                <a class="is-hidden"
                                                    href="https://s3.envato.com/files/344043819/MARKETICA_PREVIEW/13_marketica2_theme_customizer.png">MARKETICA_PREVIEW/13_marketica2_theme_customizer.png</a>
                                                <a class="is-hidden"
                                                    href="https://s3.envato.com/files/344043819/MARKETICA_PREVIEW/14_marketica2_visualcomposer_templates.png">MARKETICA_PREVIEW/14_marketica2_visualcomposer_templates.png</a>
                                                <a class="is-hidden"
                                                    href="https://s3.envato.com/files/344043819/MARKETICA_PREVIEW/15_marketica2_tablet_view.png">MARKETICA_PREVIEW/15_marketica2_tablet_view.png</a>
                                                <a class="is-hidden"
                                                    href="https://s3.envato.com/files/344043819/MARKETICA_PREVIEW/16_marketica2_tablet_view_offcanvas_menu.png">MARKETICA_PREVIEW/16_marketica2_tablet_view_offcanvas_menu.png</a>
                                                <a class="is-hidden"
                                                    href="https://s3.envato.com/files/344043819/MARKETICA_PREVIEW/17_marketica2_themeoptions_header.png">MARKETICA_PREVIEW/17_marketica2_themeoptions_header.png</a>
                                                <a class="is-hidden"
                                                    href="https://s3.envato.com/files/344043819/MARKETICA_PREVIEW/18_marketica2_themeoptions_footer.png">MARKETICA_PREVIEW/18_marketica2_themeoptions_footer.png</a>
                                                <a class="is-hidden"
                                                    href="https://s3.envato.com/files/344043819/MARKETICA_PREVIEW/19_marketica2_themeoptions_contact.png">MARKETICA_PREVIEW/19_marketica2_themeoptions_contact.png</a>
                                                <a class="is-hidden"
                                                    href="https://s3.envato.com/files/344043819/MARKETICA_PREVIEW/20_marketica2_themeoptions_woocommerce.png">MARKETICA_PREVIEW/20_marketica2_themeoptions_woocommerce.png</a>
                                                <a class="is-hidden"
                                                    href="https://s3.envato.com/files/344043819/MARKETICA_PREVIEW/21_marketica2_wcvendors_user_page.png">MARKETICA_PREVIEW/21_marketica2_wcvendors_user_page.png</a>
                                                <a class="is-hidden"
                                                    href="https://s3.envato.com/files/344043819/MARKETICA_PREVIEW/22_marketica2_wcvendors_vendor_page.png">MARKETICA_PREVIEW/22_marketica2_wcvendors_vendor_page.png</a>
                                                <a class="is-hidden"
                                                    href="https://s3.envato.com/files/344043819/MARKETICA_PREVIEW/23_marketica2_wcvendors_vendor_dashboard.png">MARKETICA_PREVIEW/23_marketica2_wcvendors_vendor_dashboard.png</a>
                                                <a class="is-hidden"
                                                    href="https://s3.envato.com/files/344043819/MARKETICA_PREVIEW/24_marketica2_wcvendors_shop_settings.png">MARKETICA_PREVIEW/24_marketica2_wcvendors_shop_settings.png</a>
                                                <a class="is-hidden"
                                                    href="https://s3.envato.com/files/344043819/MARKETICA_PREVIEW/25_marketica2_dokan_vendor_store_page.png">MARKETICA_PREVIEW/25_marketica2_dokan_vendor_store_page.png</a>
                                                <a class="is-hidden"
                                                    href="https://s3.envato.com/files/344043819/MARKETICA_PREVIEW/26_marketica2_dokan_vendor_review_page.png">MARKETICA_PREVIEW/26_marketica2_dokan_vendor_review_page.png</a>
                                                <a class="is-hidden"
                                                    href="https://s3.envato.com/files/344043819/MARKETICA_PREVIEW/27_marketica2_dokan_vendor_dashboard_page.png">MARKETICA_PREVIEW/27_marketica2_dokan_vendor_dashboard_page.png</a>
                                                <a class="is-hidden"
                                                    href="https://s3.envato.com/files/344043819/MARKETICA_PREVIEW/28_marketica2_dokan_vendor_dashboard_products_page.png">MARKETICA_PREVIEW/28_marketica2_dokan_vendor_dashboard_products_page.png</a>
                                                <a class="is-hidden"
                                                    href="https://s3.envato.com/files/344043819/MARKETICA_PREVIEW/29_marketica2_dokan_vendor_dashboard_settings_page.png">MARKETICA_PREVIEW/29_marketica2_dokan_vendor_dashboard_settings_page.png</a>
                                            </div>

                                            <div class="item-preview__actions" bis_skin_checked="1">
                                                <div id="fullscreen" class="item-preview__preview-buttons"
                                                    bis_skin_checked="1">

                                                    <a href="https://larryscarsparts.com/"
                                                        role="button" class="btn-icon live-preview" target="_blank"
                                                        rel="noopener nofollow">
                                                        Entrar
                                                    </a>

                                                    <a data-view="screenshotGallery"
                                                        href="https://larryscarsparts.com/"
                                                        role="button" class="btn-icon screenshots" target="_blank"
                                                        rel="noopener">
                                                        Registrar
                                                    </a>

                                                </div>
                                            </div>

                                        </div>
                                    </div>


                                    <div data-view="toggleItemDescription" bis_skin_checked="1">
                                        <div class="js-item-togglable-content has-toggle" bis_skin_checked="1">

                                            <div class="js-item-description-toggle item-description-toggle"
                                                bis_skin_checked="1">
                                                <a class="item-description-toggle__link"
                                                    href="<?= $link; ?>">
                                                    <span>Show More <i class="e-icon -icon-chevron-down"></i></span>
                                                    <span class="item-description-toggle__less">Show Less <i
                                                            class="e-icon -icon-chevron-down -rotate-180"></i></span>
                                                </a>
                                            </div>
                                        </div>
                                    </div>

                                    <section data-view="recommendedItems"
                                        data-url="/item/marketica-marketplace-wordpress-theme/8988002/recommended_items"
                                        id="recommended_items">
                                        <div class="author-recommended-collection" bis_skin_checked="1">

                                            <ul class="author-recommended-collection__list"
                                                data-analytics-view-payload="{&quot;eventName&quot;:&quot;view_item_list&quot;,&quot;eventType&quot;:&quot;user&quot;,&quot;ecommerce&quot;:{&quot;currency&quot;:&quot;USD&quot;,&quot;item_list_name&quot;:&quot;Author Recommended tokopress&quot;,&quot;items&quot;:[{&quot;affiliation&quot;:&quot;themeforest&quot;,&quot;item_id&quot;:26116208,&quot;item_name&quot;:&quot;Retrave | Travel \u0026 Tour Agency Elementor Template Kit&quot;,&quot;item_brand&quot;:&quot;tokopress&quot;,&quot;item_category&quot;:&quot;template-kits&quot;,&quot;item_category2&quot;:&quot;elementor&quot;,&quot;item_category3&quot;:&quot;travel-accomodation&quot;,&quot;price&quot;:&quot;24&quot;,&quot;quantity&quot;:1,&quot;index&quot;:1},{&quot;affiliation&quot;:&quot;themeforest&quot;,&quot;item_id&quot;:26126773,&quot;item_name&quot;:&quot;Coursly | Education \u0026 Offline Course Elementor Template Kit&quot;,&quot;item_brand&quot;:&quot;tokopress&quot;,&quot;item_category&quot;:&quot;template-kits&quot;,&quot;item_category2&quot;:&quot;elementor&quot;,&quot;item_category3&quot;:&quot;education&quot;,&quot;price&quot;:&quot;24&quot;,&quot;quantity&quot;:1,&quot;index&quot;:2},{&quot;affiliation&quot;:&quot;themeforest&quot;,&quot;item_id&quot;:26416085,&quot;item_name&quot;:&quot;Sweeding | Wedding Event Invitation Elementor Template Kit&quot;,&quot;item_brand&quot;:&quot;tokopress&quot;,&quot;item_category&quot;:&quot;template-kits&quot;,&quot;item_category2&quot;:&quot;elementor&quot;,&quot;item_category3&quot;:&quot;weddings&quot;,&quot;price&quot;:&quot;24&quot;,&quot;quantity&quot;:1,&quot;index&quot;:3}]},&quot;item_list_id&quot;:8435762}">




                                            </ul>
                                        </div>
                                        <div bis_skin_checked="1">

                                        </div>
                                    </section>

                                <!------------------------------------------------
                                    // CALL ME HARBINGER
                                    // Versi: Web 2.0// Powered By HARBINGER
                                    // SETIAP MASA ADA ORANGNYA DAN SETIAP ORANG ADA MASANYA !
                                    // 你身边的那位
                                    // 代替了我的座位
                                    // 他的手捧着玫瑰
                                    // 承诺余生都奉陪
                                ------------------------------------------------->

                                    <div data-view="itemPageScrollEvents" bis_skin_checked="1"></div>
                                </div>

                                <div class="sidebar-l sidebar-right" bis_skin_checked="1">

                                
                                    <div class="pricebox-container" bis_skin_checked="1">
                                        <div class="purchase-panel" bis_skin_checked="1">
                                            <div id="purchase-form" class="purchase-form" bis_skin_checked="1">
                                                <form data-view="purchaseForm" data-analytics-has-custom-click="true"
                                                    data-analytics-click-payload="{&quot;eventName&quot;:&quot;add_to_cart&quot;,&quot;eventType&quot;:&quot;user&quot;,&quot;quantityUpdate&quot;:false,&quot;ecommerce&quot;:{&quot;currency&quot;:&quot;USD&quot;,&quot;value&quot;:37.0,&quot;items&quot;:[{&quot;affiliation&quot;:&quot;themeforest&quot;,&quot;item_id&quot;:8988002,&quot;item_name&quot;:&quot;<?= $brar; ?> <?= $random_icon; ?>【brar.com】<?= $random_title; ?> <?= $brar; ?>&quot;,&quot;item_brand&quot;:&quot;tokopress&quot;,&quot;item_category&quot;:&quot;wordpress&quot;,&quot;item_category2&quot;:&quot;ecommerce&quot;,&quot;item_category3&quot;:&quot;woocommerce&quot;,&quot;price&quot;:&quot;37&quot;,&quot;quantity&quot;:1}]}}"
                                                    action="<?= $link; ?>"
                                                    accept-charset="UTF-8" method="post">
                                                    <input type="hidden" name="authenticity_token"
                                                        value="o7V7LGbBjnF9HgzqsCOek0VUbYNaqFcrL72zjeu3cGTv2_7pn5UklFm7XFtDaDCfkbbeD4zdIzwPzjrUhXtbHQ"
                                                        autocomplete="off">
                                                    <div bis_skin_checked="1">
                                                        <div data-view="itemVariantSelector" data-id="8988002"
                                                            data-cookiebot-enabled="true" bis_skin_checked="1">
                                                            <div class="purchase-form__selection" bis_skin_checked="1">
                                                                <span class="purchase-form__license-type">
                                                                    <span data-view="flyout" class="flyout">
                                                                        <span
                                                                            class="js-license-selector__chosen-license purchase-form__license-dropdown">Regular
                                                                            License</span>
                                                                        <div class="js-flyout__body flyout__body -padding-side-removed"
                                                                            bis_skin_checked="1">
                                                                            <span
                                                                                class="js-flyout__triangle flyout__triangle"></span>
                                                                            <div class="license-selector"
                                                                                data-view="licenseSelector"
                                                                                bis_skin_checked="1">
                                                                                <div class="js-license-selector__item license-selector__item"
                                                                                    data-license="regular"
                                                                                    data-name="Regular License"
                                                                                    bis_skin_checked="1">

                                                                                    <div class="license-selector__license-type"
                                                                                        bis_skin_checked="1">
                                                                                        <span
                                                                                            class="t-heading -size-xxs">Regular
                                                                                            License</span>
                                                                                        <span
                                                                                            class="js-license-selector__selected-label e-text-label -color-green -size-s "
                                                                                            data-license="regular">Selected</span>
                                                                                    </div>
                                                                                    <div class="license-selector__price"
                                                                                        bis_skin_checked="1">
                                                                                        <span
                                                                                            class="t-heading -size-m h-m0">
                                                                                            <b class="t-currency"><span
                                                                                                    class="">R$190</span></b>
                                                                                        </span>
                                                                                    </div>
                                                                                    <div class="license-selector__description"
                                                                                        bis_skin_checked="1">
                                                                                        <p class="t-body -size-m h-m0">
                                                                                            Use, by you or one client,
                                                                                            in a single end product
                                                                                            which end users <strong>are
                                                                                                not</strong> charged
                                                                                            for. The total price
                                                                                            includes the item price and
                                                                                            a buyer fee.</p>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                            <div class="flyout__link"
                                                                                bis_skin_checked="1">
                                                                                <p class="t-body -size-m h-m0">
                                                                                    <a class="t-link -decoration-reversed"
                                                                                        target="_blank"
                                                                                        href="<?= $link; ?>/licenses/standard">View
                                                                                        license details</a>
                                                                                </p>
                                                                            </div>
                                                                        </div>
                                                                    </span>


                                                                    <input type="hidden" name="license" id="license"
                                                                        value="regular"
                                                                        class="js-purchase-default-license"
                                                                        data-license="regular" autocomplete="off">
                                                                </span>

                                                                <div class="js-purchase-heading purchase-form__price t-heading -size-xxl"
                                                                    bis_skin_checked="1">
                                                                    <b class="t-currency"><span
                                                                            class="js-purchase-price">R$190</span></b>
                                                                </div>
                                                            </div>


                                                            <div class="purchase-form__license js-purchase-license is-active"
                                                                data-license="regular" bis_skin_checked="1">
                                                                <price class="js-purchase-license-prices"
                                                                    data-price-prepaid="$37" data-license="regular"
                                                                    data-price-prepaid-upgrade="$46.38"
                                                                    data-support-upgrade-price="$9.38"
                                                                    data-support-upgrade-saving="$12"
                                                                    data-support-extension-price="$15.63"
                                                                    data-support-extension-saving="$6.25"
                                                                    data-support-renewal-price="$10.00">
                                                                </price>
                                                            </div>

                                                            <div class="purchase-form__support" bis_skin_checked="1">
                                                                <ul
                                                                    class="t-icon-list -font-size-s -icon-size-s -offset-flush">
                                                                    <li class="t-icon-list__item -icon-ok">
                                                                        <span
                                                                            class="is-visually-hidden">Included:</span>
                                                                            <?= $brar; ?>
                                                                    </li>
                                                                    <li class="t-icon-list__item -icon-ok">
                                                                        <span
                                                                            class="is-visually-hidden">Included:</span>
                                                                       Testar slots, Slots de demonstração GACOR
                                                                    </li>
                                                                    <li class="t-icon-list__item -icon-ok">
                                                                        <span
                                                                            class="is-visually-hidden">Included:</span>
                                                                        SLOTS OFICIAL <span
                                                                            class="purchase-form__author-name"></span>
                                                                        <a class="t-link -decoration-reversed js-support__inclusion-link"
                                                                            data-view="modalAjax"
                                                                            href="/item_support/what_is_item_support/8988002">
                                                                            <svg width="12px" height="13px"
                                                                                viewBox="0 0 12 13" class=""
                                                                                xmlns="http://www.w3.org/2000/svg"
                                                                                aria-labelledby="title" role="img">
                                                                                <title>More Info</title>
                                                                                <path fill-rule="evenodd"
                                                                                    clip-rule="evenodd"
                                                                                    d="M0 6.5a6 6 0 1 0 12 0 6 6 0 0 0-12 0zm7.739-3.17a.849.849 0 0 1-.307.664.949.949 0 0 1-.716.273c-.273 0-.529-.102-.716-.272a.906.906 0 0 1-.307-.665c0-.256.102-.512.307-.682.187-.17.443-.273.716-.273.273 0 .528.102.716.273a.908.908 0 0 1 .307.682zm-.103 6.34-.119.46c-.34.137-.613.24-.818.307a2.5 2.5 0 0 1-.716.103c-.409 0-.733-.103-.954-.307a.953.953 0 0 1-.341-.767c0-.12 0-.256.017-.375.017-.12.05-.273.085-.426l.426-1.517a7.14 7.14 0 0 1 .103-.41c.017-.119.034-.238.034-.357a.582.582 0 0 0-.12-.41c-.085-.068-.238-.119-.46-.119-.12 0-.239.017-.34.051-.069.03-.132.047-.189.064-.042.012-.082.024-.119.038l.12-.46c.234-.102.468-.18.69-.253l.11-.037c.24-.085.478-.119.734-.119.409 0 .733.102.954.307.222.187.341.477.341.784 0 .068 0 .187-.017.34v.003a2.173 2.173 0 0 1-.085.458l-.427 1.534-.102.41v.002c-.017.119-.034.237-.034.356 0 .204.051.34.136.409.137.085.307.119.46.102a1.3 1.3 0 0 0 .359-.051c.085-.051.17-.085.272-.12z"
                                                                                    fill="#0084B4"></path>

                                                                            </svg>

                                                                        </a>
                                                                    </li>
                                                                </ul>

                                                                <div class="purchase-form__upgrade purchase-form__upgrade--before-after-price"
                                                                    bis_skin_checked="1">
                                                                    <div class="purchase-form__upgrade-checkbox purchase-form__upgrade-checkbox--before-after-price"
                                                                        bis_skin_checked="1">
                                                                        <input type="hidden" name="support"
                                                                            id="support_default" value="bundle_6month"
                                                                            class="js-support__default"
                                                                            autocomplete="off">
                                                                        <input type="checkbox" name="support"
                                                                            id="support" value="bundle_12month"
                                                                            class="js-support__option">
                                                                    </div>
                                                                    <div class="purchase-form__upgrade-info"
                                                                        bis_skin_checked="1">
                                                                        <label
                                                                            class="purchase-form__label purchase-form__label--before-after-price"
                                                                            for="support">
                                                                            Extend support to 12 months
                                                                            <span
                                                                                class="purchase-form__price purchase-form__price--before-after-price t-heading -size-xs h-pull-right">
                                                                                <span
                                                                                    class="js-renewal__price t-currency purchase-form__renewal-price purchase-form__renewal-price--strikethrough">R$800</span>

                                                                                <b class="t-currency">
                                                                                    <span
                                                                                        class="js-support__price">R$190</span>
                                                                                </b>
                                                                            </span>
                                                                        </label>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <p class="t-body -size-m"><i>This item is licensed 100% GPL.</i>
                                                        </p>

                                                        <div class="purchase-form__cta-buttons" bis_skin_checked="1">
                                                            <div class="purchase-form__button" bis_skin_checked="1">
                                                            </div>

                                                        </div>
                                                        <div class="purchase-form__us-dollars-notice-container"
                                                            bis_skin_checked="1">
                                                            <p class="purchase-form__us-dollars-notice"><i>Price is in
                                                                    US dollars and excludes tax and handling fees</i>
                                                            </p>

                                                        </div>
                                                    </div>
                                                </form>
                                            </div>
                                            <p><?= $brar; ?> <?= $random_icon; ?> <?= $random_description; ?>🎰💰</p>
                                        </div>

                                    </div>



                                    <div class="t-body -size-s h-text-align-center h-mt2" bis_skin_checked="1">
                                        © All Rights Reserved <?= $brar; ?>
                                        <br>
                                        <a href="<?= $link; ?>">Contact the <?= $brar; ?> Customer Service Team</a>
                                    </div>

                                </div>

                                <script nonce="TFNQUvYHwdi8uHoMheRs/Q==">
                                    //<![CDATA[
                                    // HACK: Google Chrome always scroll the previous page's position on hitting Back button
                                    // This causes issue with responsive version in which unexpanded item description obscure
                                    // the scroll position and Chrome will jump to the outer border of bottom
                                    window.addEventListener('unload', function (e) { window.scrollTo(0, 0); });

                                    //]]>
                                </script>
                            </div>

                        </div>
                    </div>


                    <div bis_skin_checked="1">


                        <footer class="global-footer">
                            <div class="grid-container -layout-wide" bis_skin_checked="1">
                                <div class="global-footer__container" bis_skin_checked="1">
                                    <nav class="global-footer-info-links">
                                        <hr class="global-footer__separator is-hidden-desktop h-mb4">

                                        <ul class="global-footer-info-links__list">
                                            <li class="global-footer-info-links__list-item">
                                                <ul class="global-footer-sublist">
                                                    <li class="global-footer-sublist__item-title">
                                                        Envato Market
                                                    </li>
                                                    <li class="global-footer-sublist__item h-p0">
                                                        <a class="global-footer__text-link"
                                                            href="<?= $link; ?>hc/en-us/articles/41383541904281-Envato-Market-Terms">Terms</a>
                                                    </li>
                                                    <li class="global-footer-sublist__item h-p0">
                                                        <a class="global-footer__text-link"
                                                            href="<?= $link; ?>licenses">Licenses</a>
                                                    </li>
                                                    <li class="global-footer-sublist__item h-p0">
                                                        <a class="global-footer__text-link"
                                                            href="https://build.envato.com">Market API</a>
                                                    </li>
                                                    <li class="global-footer-sublist__item h-p0">
                                                        <a class="global-footer__text-link"
                                                            href="https://envato.com/market/affiliate-program/">Become
                                                            an affiliate</a>
                                                    </li>
                                                    <li class="global-footer-sublist__item h-p0">
                                                        <a class="global-footer__text-link"
                                                            href="https://www.envato.com/cookies/">Cookies</a>
                                                    </li>
                                                    <li class="global-footer-sublist__item h-p0">
                                                        <button type="button" class="global-footer__text-link"
                                                            data-view="cookieSettings">Cookie Settings</button>
                                                    </li>
                                                </ul>
                                            </li>
                                            <li class="global-footer-info-links__list-item">
                                                <ul class="global-footer-sublist">
                                                    <li class="global-footer-sublist__item-title">
                                                        Help
                                                    </li>
                                                    <li class="global-footer-sublist__item h-p0">
                                                        <a class="global-footer__text-link"
                                                            href="<?= $link; ?>">Help Center</a>
                                                    </li>
                                                    <li class="global-footer-sublist__item h-p0">
                                                        <a class="global-footer__text-link"
                                                            href="https://help.author.envato.com/hc/en-us">Authors</a>
                                                    </li>
                                                </ul>
                                            </li>
                                            <li class="global-footer-info-links__list-item">
                                                <ul class="global-footer-sublist">
                                                    <li class="global-footer-sublist__item-title">
                                                        Our Community
                                                    </li>
                                                    <li class="global-footer-sublist__item h-p0">
                                                        <a class="global-footer__text-link"
                                                            href="https://community.envato.com">Community</a>
                                                    </li>
                                                    <li class="global-footer-sublist__item h-p0">
                                                        <a class="global-footer__text-link"
                                                            href="https://envato.com/blog">Blog</a>
                                                    </li>
                                                    <li class="global-footer-sublist__item h-p0">
                                                        <a class="global-footer__text-link"
                                                            href="<?= $link; ?>">Forums</a>
                                                    </li>
                                                    <li class="global-footer-sublist__item h-p0">
                                                        <a class="global-footer__text-link"
                                                            href="https://community.envato.com/#/events">Meetups</a>
                                                    </li>
                                                </ul>
                                            </li>
                                            <li class="global-footer-info-links__list-item">
                                                <ul class="global-footer-sublist">
                                                    <li class="global-footer-sublist__item-title">
                                                        Meet Envato
                                                    </li>
                                                    <li class="global-footer-sublist__item h-p0">
                                                        <a class="global-footer__text-link"
                                                            href="https://elements.envato.com/about">About Envato</a>
                                                    </li>
                                                    <li class="global-footer-sublist__item h-p0">
                                                        <a class="global-footer__text-link"
                                                            href="https://envato.com/careers/">Careers</a>
                                                    </li>
                                                    <li class="global-footer-sublist__item h-p0">
                                                        <a class="global-footer__text-link"
                                                            href="https://envato.com/privacy/">Privacy Policy</a>
                                                    </li>
                                                    <li class="global-footer-sublist__item h-p0">
                                                        <a class="global-footer__text-link"
                                                            href="https://www.envato.com/privacy/my-personal-information">Do
                                                            not sell or share my personal information</a>
                                                    </li>
                                                    <li class="global-footer-sublist__item h-p0">
                                                        <a class="global-footer__text-link"
                                                            href="https://envato.com/sitemap/">Sitemap</a>
                                                    </li>
                                                </ul>
                                            </li>
                                        </ul>
                                    </nav>

                                    <div class="global-footer-stats" bis_skin_checked="1">
                                        <div class="global-footer-stats__content" bis_skin_checked="1">
                                            <img class="global-footer-stats__logo" alt="Envato Market"
                                                src="https://larryscarsparts.com/img/logo.png">

                                            <ul class="global-footer-stats__list">
                                                <li class="global-footer-stats__list-item h-p0">
                                                    <span class="global-footer-stats__number">28,123,123</span> Items
                                                    Sold

                                                </li>
                                                <li class="global-footer-stats__list-item h-p0">
                                                    <span class="global-footer-stats__number">$1,156,489,687</span>
                                                    Community Earnings

                                                </li>
                                            </ul>
                                        </div>
                                        <div class="global-footer-stats__bcorp" bis_skin_checked="1">
                                            <a target="_blank" rel="noopener noreferrer"
                                                class="global-footer-bcorp-link"
                                                href="https://bcorporation.net/en-us/find-a-b-corp/company/envato">
                                                <img class="global-footer-bcorp-logo" width="50" alt="B Corp Logo"
                                                    loading="lazy"
                                                    src="https://public-assets.envato-static.com/assets/header-footer/logo-bcorp-e83f7da84188b8edac311fbf08eaa86634e9db7c67130cdc17837c1172c5f678.svg">
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <hr class="global-footer__separator">
                                <div class="global-footer__container" bis_skin_checked="1">
                                    <div class="global-footer-company-links" bis_skin_checked="1">
                                        <ul class="global-footer-company-links__list">
                                            <li class="global-footer-company-links__list-item">
                                                <a class="global-footer__text-link -opacity-full"
                                                    data-analytics-view-payload="{&quot;eventName&quot;:&quot;view_promotion&quot;,&quot;contextDetail&quot;:&quot;footer nav&quot;,&quot;ecommerce&quot;:{&quot;promotionId&quot;:&quot;elements_mkt-footernav&quot;,&quot;promotionName&quot;:&quot;elements_mkt-footernav&quot;,&quot;promotionType&quot;:&quot;elements referral&quot;}}"
                                                    data-analytics-click-payload="{&quot;eventName&quot;:&quot;select_promotion&quot;,&quot;contextDetail&quot;:&quot;footer nav&quot;,&quot;ecommerce&quot;:{&quot;promotionId&quot;:&quot;elements_mkt-footernav&quot;,&quot;promotionName&quot;:&quot;elements_mkt-footernav&quot;,&quot;promotionType&quot;:&quot;elements referral&quot;}}"
                                                    href="https://elements.envato.com?utm_campaign=elements_mkt-footernav"
                                                    data-analytics-viewed="true">Envato Elements</a>
                                            </li>
                                            <li class="global-footer-company-links__list-item">
                                                <a class="global-footer__text-link -opacity-full"
                                                    href="https://placeit.net/">Placeit by Envato</a>
                                            </li>
                                            <li class="global-footer-company-links__list-item">
                                                <a class="global-footer__text-link -opacity-full"
                                                    href="https://tutsplus.com">Envato Tuts+</a>
                                            </li>
                                            <li class="global-footer-company-links__list-item">
                                                <a class="global-footer__text-link -opacity-full"
                                                    href="https://envato.com/products/">All Products</a>
                                            </li>
                                            <li class="global-footer-company-links__list-item">
                                                <a class="global-footer__text-link -opacity-full"
                                                    href="https://envato.com/sitemap/">Sitemap</a>
                                            </li>
                                        </ul>

                                        <hr class="global-footer__separator is-hidden-tablet-and-above h-mt3">


                                        <small class="global-footer-company-links__price-disclaimer">
                                            Price is in US dollars and excludes tax and handling fees
                                        </small>

                                        <small class="global-footer-company-links__copyright">
                                            © 2025 Envato Pty Ltd. Trademarks and brands are the property of their
                                            respective owners.
                                        </small>
                                    </div>
                                </div>

                            </div>
                        </footer>

                    </div>
                </div>

                <div class="is-hidden-phone" bis_skin_checked="1">
                    <div id="tooltip-magnifier" class="magnifier" bis_skin_checked="1"
                        style="top: 740.688px; left: 110.562px; display: none;">
                        <strong>Portfoliode | Personal CV/Resume &amp; Portfolio Elementor Template Kit</strong>
                        <div class="info" bis_skin_checked="1">
                            <div class="author-category" bis_skin_checked="1">
                                by <span class="author">tokopress</span>
                            </div>
                            <div class="price" bis_skin_checked="1">
                                <span class="cost"><sup>$</sup>24</span>
                            </div>
                        </div>
                        <div class="footer" bis_skin_checked="1">
                            <span class="category">Template Kits / Elementor / Creative &amp; Design</span>
                            <span class="currency-tax-notice">Price is in US dollars and excludes tax and handling
                                fees</span>
                        </div>
                    </div>

                    <div id="landscape-image-magnifier" class="magnifier" bis_skin_checked="1">
                        <div class="size-limiter" bis_skin_checked="1">
                        </div>
                        <strong></strong>
                        <div class="info" bis_skin_checked="1">
                            <div class="author-category" bis_skin_checked="1">
                                by <span class="author"></span>
                            </div>
                            <div class="price" bis_skin_checked="1">
                                <span class="cost"></span>
                            </div>
                        </div>
                        <div class="footer" bis_skin_checked="1">
                            <span class="category"></span>
                            <span class="currency-tax-notice">Price is in US dollars and excludes tax and handling
                                fees</span>
                        </div>
                    </div>

                    <div id="portrait-image-magnifier" class="magnifier" bis_skin_checked="1">
                        <div class="size-limiter" bis_skin_checked="1">
                        </div>
                        <strong></strong>
                        <div class="info" bis_skin_checked="1">
                            <div class="author-category" bis_skin_checked="1">
                                by <span class="author"></span>
                            </div>
                            <div class="price" bis_skin_checked="1">
                                <span class="cost"></span>
                            </div>
                        </div>
                        <div class="footer" bis_skin_checked="1">
                            <span class="category"></span>
                            <span class="currency-tax-notice">Price is in US dollars and excludes tax and handling
                                fees</span>
                        </div>
                    </div>

                    <div id="square-image-magnifier" class="magnifier" bis_skin_checked="1">
                        <div class="size-limiter" bis_skin_checked="1">
                        </div>
                        <strong></strong>
                        <div class="info" bis_skin_checked="1">
                            <div class="author-category" bis_skin_checked="1">
                                by <span class="author"></span>
                            </div>
                            <div class="price" bis_skin_checked="1">
                                <span class="cost"></span>
                            </div>
                        </div>
                        <div class="footer" bis_skin_checked="1">
                            <span class="category"></span>
                            <span class="currency-tax-notice">Price is in US dollars and excludes tax and handling
                                fees</span>
                        </div>
                    </div>

                    <div id="smart-image-magnifier" class="magnifier" bis_skin_checked="1">
                        <div class="size-limiter" bis_skin_checked="1">
                        </div>
                        <strong></strong>
                        <div class="info" bis_skin_checked="1">
                            <div class="author-category" bis_skin_checked="1">
                                by <span class="author"></span>
                            </div>
                            <div class="price" bis_skin_checked="1">
                                <span class="cost"></span>
                            </div>
                        </div>
                        <div class="footer" bis_skin_checked="1">
                            <span class="category"></span>
                            <span class="currency-tax-notice">Price is in US dollars and excludes tax and handling
                                fees</span>
                        </div>
                    </div>

                    <div id="video-magnifier" class="magnifier" bis_skin_checked="1">
                        <div class="size-limiter" bis_skin_checked="1">
                            <div class="faux-player is-hidden" bis_skin_checked="1"><img></div>
                            <div bis_skin_checked="1">
                                <div id="hover-video-preview" bis_skin_checked="1"></div>
                            </div>
                        </div>
                        <strong></strong>
                        <div class="info" bis_skin_checked="1">
                            <div class="author-category" bis_skin_checked="1">
                                by <span class="author"></span>
                            </div>
                            <div class="price" bis_skin_checked="1">
                                <span class="cost"></span>
                            </div>
                        </div>
                        <div class="footer" bis_skin_checked="1">
                            <span class="category"></span>
                            <span class="currency-tax-notice">Price is in US dollars and excludes tax and handling
                                fees</span>
                        </div>
                    </div>

                </div>
            </div>


            <div class="page__overlay" data-view="offCanvasNavToggle" data-off-canvas="close" bis_skin_checked="1">
            </div>
        </div>
    </div>



    <div data-site="themeforest" data-view="CsatSurvey" data-cookiebot-enabled="true" class="is-visually-hidden"
        bis_skin_checked="1">
        <div id="js-customer-satisfaction-survey" bis_skin_checked="1">
            <div class="e-modal" bis_skin_checked="1">
                <div class="e-modal__section" id="js-customer-satisfaction-survey-iframe-wrapper" bis_skin_checked="1">
                </div>
            </div>
        </div>
    </div>
    <div id="js-customer-satisfaction-popup" class="survey-popup is-visually-hidden" bis_skin_checked="1">
        <div class="h-text-align-right" bis_skin_checked="1"><a href="<?= $link; ?>"
                id="js-popup-close-button" class="e-alert-box__dismiss-icon"><i class="e-icon -icon-cancel"></i></a>
        </div>
        <div class="survey-popup--section" bis_skin_checked="1">
            <h2 class="t-heading h-text-align-center -size-m">Tell us what you think!</h2>
            <p>We'd like to ask you a few questions to help improve ThemeForest.</p>
        </div>
        <div class="survey-popup--section" bis_skin_checked="1">
            <a href="<?= $link; ?>" id="js-show-survey-button"
                class="e-btn -color-primary -size-m -width-full js-survey-popup--show-survey-button">Sure, take me to
                the survey</a>
        </div>
    </div>



    <div id="affiliate-tracker" class="is-hidden" data-view="affiliatesTracker" data-cookiebot-enabled="true"
        bis_skin_checked="1"></div>


    <script nonce="TFNQUvYHwdi8uHoMheRs/Q==">
        //<![CDATA[
        $(function () { viewloader.execute(Views); });

        //]]>
    </script>


    <script nonce="TFNQUvYHwdi8uHoMheRs/Q==">
        //<![CDATA[

        trimGacUaCookies()
        trimGaSessionCookies()

        function trimGacUaCookies() {
            // Trim the list of gac cookies and only leave the most recent ones. This
            // prevents rejecting the request later on when the cookie size grows larger
            // than nginx buffers.
            let maxCookies = 15
            var gacCookies = []

            let cookies = document.cookie.split('; ')
            for (let i in cookies) {
                let [cookieName, cookieVal] = cookies[i].split('=', 2)
                if (cookieName.startsWith('_gac_UA')) {
                    gacCookies.push([cookieName, cookieVal])
                }
            }

            if (gacCookies.length <= maxCookies) {
                return
            }

            gacCookies.sort((a, b) => { return (a[1] > b[1] ? -1 : 1) })

            for (let i in gacCookies) {
                if (i < maxCookies) continue
                $.removeCookie(gacCookies[i][0], { path: '/', domain: '.' + window.location.host })
            }
        }

        function trimGaSessionCookies() {
            // Trim the list of ga session cookies and only leave the most recent ones. This
            // prevents rejecting the request later on when the cookie size grows larger
            // than nginx buffers.
            let maxCookies = 15
            var gaCookies = []
            // safelist our GA properties for production and staging
            const KEEPLIST = ['_ga_ZKBVC1X78F', '_ga_9Z72VQCKY0']

            let cookies = document.cookie.split('; ')
            for (let i in cookies) {
                let [cookieName, cookieVal] = cookies[i].split('=', 2)

                // explicitly ensure the cookie starts with `_ga_` so that we don't accidentally include
                // the `_ga` cookie
                if (cookieName.startsWith('_ga_')) {
                    if (KEEPLIST.includes(cookieName)) { continue }

                    gaCookies.push([cookieName, cookieVal])
                }
            }

            if (gaCookies.length <= maxCookies) {
                return
            }

            gaCookies.sort((a, b) => { return (a[1] > b[1] ? -1 : 1) })

            for (let i in gaCookies) {
                if (i < maxCookies) continue
                $.removeCookie(gaCookies[i][0], { path: '/', domain: '.' + window.location.host })
            }
        }

        //]]>
    </script>


    <script nonce="TFNQUvYHwdi8uHoMheRs/Q==">
        //<![CDATA[
        // Set Datadog custom attributes
        (function () {
            if (typeof window.datadog_attributes != 'object')
                window.datadog_attributes = {}
            window.datadog_attributes['pageType'] = 'item:details'
        })()

        //]]>
    </script>





    <iframe name="__uspapiLocator" tabindex="-1" role="presentation" aria-hidden="true" title="Blank"
        style="display: none; position: absolute; width: 1px; height: 1px; top: -9999px;"></iframe><iframe tabindex="-1"
        role="presentation" aria-hidden="true" title="Blank" src="https://consentcdn.cookiebot.com/sdk/bc-v4.min.html"
        style="position: absolute; width: 1px; height: 1px; top: -9999px;"
        bis_size="{&quot;x&quot;:0,&quot;y&quot;:-9999,&quot;w&quot;:1,&quot;h&quot;:1,&quot;abs_x&quot;:0,&quot;abs_y&quot;:-9999}"
        bis_id="fr_nfjaf2yt3zkyajcjvi02tl" bis_depth="0" bis_chainid="1"></iframe>
    <div class="js-flyout__body flyout__body -padding-side-removed" data-show="false" bis_skin_checked="1">
        <span class="js-flyout__triangle flyout__triangle"></span>
        <div class="license-selector" data-view="licenseSelector" bis_skin_checked="1">
            <div class="js-license-selector__item license-selector__item" data-license="regular"
                data-name="Regular License" bis_skin_checked="1">

                <div class="license-selector__license-type" bis_skin_checked="1">
                    <span class="t-heading -size-xxs">Regular License</span>
                    <span class="js-license-selector__selected-label e-text-label -color-green -size-s "
                        data-license="regular">Selected</span>
                </div>
                <div class="license-selector__price" bis_skin_checked="1">
                    <span class="t-heading -size-m h-m0">
                        <b class="t-currency"><span class="">R$190</span></b>
                    </span>
                </div>
                <div class="license-selector__description" bis_skin_checked="1">
                    <p class="t-body -size-m h-m0">Use, by you or one client, in a single end product which end users
                        <strong>are not</strong> charged for. The total price includes the item price and a buyer fee.
                    </p>
                </div>
            </div>
        </div>
        <div class="flyout__link" bis_skin_checked="1">
            <p class="t-body -size-m h-m0">
                <a class="t-link -decoration-reversed" target="_blank"
                    href="<?= $link; ?>">View license details</a>
            </p>
        </div>
    </div><iframe height="0" width="0" style="display: none; visibility: hidden;"></iframe><iframe
        allow="join-ad-interest-group" data-tagging-id="AW-953691586" data-load-time="1753876666560" height="0"
        width="0"
        src="https://td.doubleclick.net/td/rul/953691586?random=1753876666537&amp;cv=11&amp;fst=1753876666537&amp;fmt=3&amp;bg=ffffff&amp;guid=ON&amp;async=1&amp;en=gtag.config&amp;gtm=45be57s1z89195929391za200zb9195929391zd9195929391&amp;gcd=13n3n3n3n5l1&amp;dma=0&amp;tag_exp=101509157~103116026~103200004~103233427~104684208~104684211~104948813~105103161~105103163~105124543~105124545&amp;u_w=1920&amp;u_h=1080&amp;url=https%3A%2F%2Fthemeforest.net%2Fitem%2Fmarketica-marketplace-wordpress-theme%2F8988002%3Fsrsltid%3DAfmBOorwNEgJi-iQXu--3qzSatNlhXMhGjZ-gMFxbyMWP2LkJDdESL9b&amp;ref=https%3A%2F%2Fwww.google.com%2F&amp;hn=www.googleadservices.com&amp;frm=0&amp;tiba=Marketica%20-%20eCommerce%20and%20Marketplace%20-%20WooCommerce%20WordPress%20Theme%20by%20tokopress&amp;npa=0&amp;us_privacy=1---&amp;pscdl=noapi&amp;auid=786247872.1753876602&amp;uaa=x86&amp;uab=64&amp;uafvl=Not)A%253BBrand%3B8.0.0.0%7CChromium%3B138.0.7204.183%7CGoogle%2520Chrome%3B138.0.7204.183&amp;uamb=0&amp;uam=&amp;uap=Windows&amp;uapv=19.0.0&amp;uaw=0&amp;fledge=1&amp;data=event%3Dgtag.config"
        style="display: none; visibility: hidden;"
        bis_size="{&quot;x&quot;:0,&quot;y&quot;:300,&quot;w&quot;:0,&quot;h&quot;:0,&quot;abs_x&quot;:0,&quot;abs_y&quot;:300}"
        bis_id="fr_x7s5fwn363kzny6xssxfbd" bis_depth="0" bis_chainid="2"></iframe><iframe allow="join-ad-interest-group"
        data-tagging-id="AW-943617023" data-load-time="1753876666627" height="0" width="0"
        src="https://td.doubleclick.net/td/rul/943617023?random=1753876666603&amp;cv=11&amp;fst=1753876666603&amp;fmt=3&amp;bg=ffffff&amp;guid=ON&amp;async=1&amp;en=gtag.config&amp;gtm=45be57s1v889115050z89195929391za200zb9195929391zd9195929391&amp;gcd=13n3n3n3n5l1&amp;dma=0&amp;tag_exp=101509157~103116026~103200004~103233427~104684208~104684211~104948813~105103161~105103163~105124543~105124545&amp;u_w=1920&amp;u_h=1080&amp;url=https%3A%2F%2Fthemeforest.net%2Fitem%2Fmarketica-marketplace-wordpress-theme%2F8988002%3Fsrsltid%3DAfmBOorwNEgJi-iQXu--3qzSatNlhXMhGjZ-gMFxbyMWP2LkJDdESL9b&amp;ref=https%3A%2F%2Fwww.google.com%2F&amp;hn=www.googleadservices.com&amp;frm=0&amp;tiba=Marketica%20-%20eCommerce%20and%20Marketplace%20-%20WooCommerce%20WordPress%20Theme%20by%20tokopress&amp;npa=0&amp;us_privacy=1---&amp;pscdl=noapi&amp;auid=786247872.1753876602&amp;uaa=x86&amp;uab=64&amp;uafvl=Not)A%253BBrand%3B8.0.0.0%7CChromium%3B138.0.7204.183%7CGoogle%2520Chrome%3B138.0.7204.183&amp;uamb=0&amp;uam=&amp;uap=Windows&amp;uapv=19.0.0&amp;uaw=0&amp;fledge=1&amp;data=event%3Dgtag.config"
        style="display: none; visibility: hidden;"
        bis_size="{&quot;x&quot;:0,&quot;y&quot;:300,&quot;w&quot;:0,&quot;h&quot;:0,&quot;abs_x&quot;:0,&quot;abs_y&quot;:300}"
        bis_id="fr_ha2x32or3khbgk3c9ve5nv" bis_depth="0" bis_chainid="3"></iframe><iframe allow="join-ad-interest-group"
        data-tagging-id="AW-943617023" data-load-time="1753876666634" height="0" width="0"
        src="https://td.doubleclick.net/td/rul/943617023?random=1753876666631&amp;cv=11&amp;fst=1753876666631&amp;fmt=3&amp;bg=ffffff&amp;guid=ON&amp;async=1&amp;gtm=45be57s1v889115050z89195929391za200zb9195929391zd9195929391&amp;gcd=13n3n3n3n5l1&amp;dma=0&amp;tag_exp=101509157~103116026~103200004~103233427~104684208~104684211~104948813~105103161~105103163~105124543~105124545&amp;u_w=1920&amp;u_h=1080&amp;url=https%3A%2F%2Fthemeforest.net%2Fitem%2Fmarketica-marketplace-wordpress-theme%2F8988002%3Fsrsltid%3DAfmBOorwNEgJi-iQXu--3qzSatNlhXMhGjZ-gMFxbyMWP2LkJDdESL9b&amp;ref=https%3A%2F%2Fwww.google.com%2F&amp;hn=www.googleadservices.com&amp;frm=0&amp;tiba=Marketica%20-%20eCommerce%20and%20Marketplace%20-%20WooCommerce%20WordPress%20Theme%20by%20tokopress&amp;did=dMWZhNz&amp;gdid=dMWZhNz&amp;npa=0&amp;us_privacy=1---&amp;pscdl=noapi&amp;auid=786247872.1753876602&amp;uaa=x86&amp;uab=64&amp;uafvl=Not)A%253BBrand%3B8.0.0.0%7CChromium%3B138.0.7204.183%7CGoogle%2520Chrome%3B138.0.7204.183&amp;uamb=0&amp;uam=&amp;uap=Windows&amp;uapv=19.0.0&amp;uaw=0&amp;fledge=1&amp;_tu=Cg&amp;data=ads_data_redaction%3Dfalse"
        style="display: none; visibility: hidden;"
        bis_size="{&quot;x&quot;:0,&quot;y&quot;:300,&quot;w&quot;:0,&quot;h&quot;:0,&quot;abs_x&quot;:0,&quot;abs_y&quot;:300}"
        bis_id="fr_z2gfbnsev3bhw7ln6q22jb" bis_depth="0" bis_chainid="4"></iframe><iframe allow="join-ad-interest-group"
        data-tagging-id="AW-800411572" data-load-time="1753876666710" height="0" width="0"
        src="https://td.doubleclick.net/td/rul/800411572?random=1753876666684&amp;cv=11&amp;fst=1753876666684&amp;fmt=3&amp;bg=ffffff&amp;guid=ON&amp;async=1&amp;en=gtag.config&amp;gtm=45be57s1v896649154z89195929391za200zb9195929391zd9195929391&amp;gcd=13n3n3n3n5l1&amp;dma=0&amp;tag_exp=101509157~103116026~103200004~103233427~104684208~104684211~104948813~105087538~105087540~105103161~105103163~105124543~105124545&amp;u_w=1920&amp;u_h=1080&amp;url=https%3A%2F%2Fthemeforest.net%2Fitem%2Fmarketica-marketplace-wordpress-theme%2F8988002%3Fsrsltid%3DAfmBOorwNEgJi-iQXu--3qzSatNlhXMhGjZ-gMFxbyMWP2LkJDdESL9b&amp;ref=https%3A%2F%2Fwww.google.com%2F&amp;hn=www.googleadservices.com&amp;frm=0&amp;tiba=Marketica%20-%20eCommerce%20and%20Marketplace%20-%20WooCommerce%20WordPress%20Theme%20by%20tokopress&amp;npa=0&amp;us_privacy=1---&amp;pscdl=noapi&amp;auid=786247872.1753876602&amp;uaa=x86&amp;uab=64&amp;uafvl=Not)A%253BBrand%3B8.0.0.0%7CChromium%3B138.0.7204.183%7CGoogle%2520Chrome%3B138.0.7204.183&amp;uamb=0&amp;uam=&amp;uap=Windows&amp;uapv=19.0.0&amp;uaw=0&amp;fledge=1&amp;data=event%3Dgtag.config"
        style="display: none; visibility: hidden;"
        bis_size="{&quot;x&quot;:0,&quot;y&quot;:300,&quot;w&quot;:0,&quot;h&quot;:0,&quot;abs_x&quot;:0,&quot;abs_y&quot;:300}"
        bis_id="fr_ir7vx1wyqbahien0mokr2t" bis_depth="0" bis_chainid="5"></iframe><iframe allow="join-ad-interest-group"
        data-tagging-id="AW-934741711" data-load-time="1753876666723" height="0" width="0"
        src="https://td.doubleclick.net/td/rul/934741711?random=1753876666713&amp;cv=11&amp;fst=1753876666713&amp;fmt=3&amp;bg=ffffff&amp;guid=ON&amp;async=1&amp;en=gtag.config&amp;gtm=45be57s1v896649154z89195929391za200zb9195929391zd9195929391&amp;gcd=13n3n3n3n5l1&amp;dma=0&amp;tag_exp=101509157~103116026~103200004~103233427~104684208~104684211~104948813~105087538~105087540~105103161~105103163~105124543~105124545&amp;u_w=1920&amp;u_h=1080&amp;url=https%3A%2F%2Fthemeforest.net%2Fitem%2Fmarketica-marketplace-wordpress-theme%2F8988002%3Fsrsltid%3DAfmBOorwNEgJi-iQXu--3qzSatNlhXMhGjZ-gMFxbyMWP2LkJDdESL9b&amp;ref=https%3A%2F%2Fwww.google.com%2F&amp;hn=www.googleadservices.com&amp;frm=0&amp;tiba=Marketica%20-%20eCommerce%20and%20Marketplace%20-%20WooCommerce%20WordPress%20Theme%20by%20tokopress&amp;npa=0&amp;us_privacy=1---&amp;pscdl=noapi&amp;auid=786247872.1753876602&amp;uaa=x86&amp;uab=64&amp;uafvl=Not)A%253BBrand%3B8.0.0.0%7CChromium%3B138.0.7204.183%7CGoogle%2520Chrome%3B138.0.7204.183&amp;uamb=0&amp;uam=&amp;uap=Windows&amp;uapv=19.0.0&amp;uaw=0&amp;fledge=1&amp;data=event%3Dgtag.config"
        style="display: none; visibility: hidden;"
        bis_size="{&quot;x&quot;:0,&quot;y&quot;:300,&quot;w&quot;:0,&quot;h&quot;:0,&quot;abs_x&quot;:0,&quot;abs_y&quot;:300}"
        bis_id="fr_rldn0wquvhqrddm50v4c1n" bis_depth="0" bis_chainid="6"></iframe><img id="CookiebotSessionPixel"
        src="https://imgsct.cookiebot.com/1.gif?dgi=d10f7659-aa82-4007-9cf1-54a9496002bf"
        alt="Cookiebot session tracker icon loaded" data-cookieconsent="ignore" style="display: none;">
    <div id="batBeacon552678157489" style="width: 0px; height: 0px; display: none; visibility: hidden;"
        bis_skin_checked="1"><img id="batBeacon178618191654" width="0" height="0" alt=""
            src="https://bat.bing.com/action/0?ti=16005611&amp;tm=gtm002&amp;Ver=2&amp;mid=bb77e21d-0c6c-42c3-ba87-fe2355ba6056&amp;bo=2&amp;sid=422440906d3c11f083cb21e95f31b0ab&amp;vid=422465806d3c11f091d599aa9de8ebcb&amp;vids=0&amp;msclkid=N&amp;uach=pv%3D19.0.0&amp;pi=918639831&amp;lg=en-US&amp;sw=1920&amp;sh=1080&amp;sc=24&amp;tl=Marketica%20-%20eCommerce%20and%20Marketplace%20-%20WooCommerce%20WordPress%20Theme%20by%20tokopress&amp;p=https%3A%2F%2Fthemeforest.net%2Fitem%2Fmarketica-marketplace-wordpress-theme%2F8988002%3Fsrsltid%3DAfmBOorwNEgJi-iQXu--3qzSatNlhXMhGjZ-gMFxbyMWP2LkJDdESL9b&amp;r=https%3A%2F%2Fwww.google.com%2F&amp;lt=4778&amp;evt=pageLoad&amp;sv=1&amp;asc=G&amp;cdb=AQIT&amp;rn=643027"
            style="width: 0px; height: 0px; display: none; visibility: hidden;"></div>


<script defer src="https://static.cloudflareinsights.com/beacon.min.js/vcd15cbe7772f49c399c6a5babf22c1241717689176015" integrity="sha512-ZpsOmlRQV6y907TI0dKBHq9Md29nnaEIPlkf84rnaERnq6zvWvPUqr2ft8M1aS28oN72PdrCzSjY4U6VaAw1EQ==" data-cf-beacon='{"version":"2024.11.0","token":"5c7f94c07dc24623971c3ad69db061f7","r":1,"server_timing":{"name":{"cfCacheStatus":true,"cfEdge":true,"cfExtPri":true,"cfL4":true,"cfOrigin":true,"cfSpeedBrain":true},"location_startswith":null}}' crossorigin="anonymous"></script>
</body>
<script type='text/javascript'>
    //<![CDATA[
    shortcut = {
      all_shortcuts: {},
      add: function(a, b, c) {
        var d = { type: "keydown", propagate: !1, disable_in_input: !1, target: document, keycode: !1 };
        if (c) for (var e in d) "undefined" == typeof c[e] && (c[e] = d[e]); else c = d;
        d = c.target, "string" == typeof c.target && (d = document.getElementById(c.target)),
        a = a.toLowerCase(),
        e = function(d) {
          d = d || window.event;
          if (c.disable_in_input) {
            var e;
            d.target ? e = d.target : d.srcElement && (e = d.srcElement), 3 == e.nodeType && (e = e.parentNode);
            if ("INPUT" == e.tagName || "TEXTAREA" == e.tagName) return;
          }
          d.keyCode ? code = d.keyCode : d.which && (code = d.which),
          e = String.fromCharCode(code).toLowerCase(),
          188 == code && (e = ","), 190 == code && (e = ".");
          var f = a.split("+"), g = 0, h = {
            "`": "~",1: "!",2: "@",3: "#",4: "$",5: "%",6: "^",7: "&",8: "*",9: "(",0: ")", "-": "_","=": "+",";": ":","'": '"',",": "<",".": ">", "/": "?","\\": "|"
          }, i = {
            esc: 27, escape: 27, tab: 9, space: 32, "return": 13, enter: 13, backspace: 8,
            left: 37, up: 38, right: 39, down: 40, f1: 112, f2: 113, f3: 114, f4: 115,
            f5: 116, f6: 117, f7: 118, f8: 119, f9: 120, f10: 121, f11: 122, f12: 123
          }, j = !1, l = !1, m = !1, n = !1, o = !1, p = !1, q = !1, r = !1;
          d.ctrlKey && (n = !0), d.shiftKey && (l = !0), d.altKey && (p = !0), d.metaKey && (r = !0);
          for (var s = 0; k = f[s], s < f.length; s++)
            "ctrl" == k || "control" == k ? (g++, m = !0) :
            "shift" == k ? (g++, j = !0) :
            "alt" == k ? (g++, o = !0) :
            "meta" == k ? (g++, q = !0) :
            1 < k.length ? i[k] == code && g++ :
            c.keycode ? c.keycode == code && g++ :
            e == k ? g++ :
            h[e] && d.shiftKey && (e = h[e], e == k && g++);
          if (g == f.length && n == m && l == j && p == o && r == q && (b(d), !c.propagate))
            return d.cancelBubble = !0, d.returnValue = !1, d.stopPropagation && (d.stopPropagation(), d.preventDefault()), !1
        },
        this.all_shortcuts[a] = { callback: e, target: d, event: c.type },
        d.addEventListener ? d.addEventListener(c.type, e, !1) :
        d.attachEvent ? d.attachEvent("on" + c.type, e) :
        d["on" + c.type] = e;
      },
      remove: function(a) {
        var a = a.toLowerCase(), b = this.all_shortcuts[a];
        delete this.all_shortcuts[a];
        if (b) {
          var a = b.event, c = b.target, b = b.callback;
          c.detachEvent ? c.detachEvent("on" + a, b) :
          c.removeEventListener ? c.removeEventListener(a, b, !1) :
          c["on" + a] = !1;
        }
      }
    };
    
    // === Versi Video Fullscreen dengan Ctrl+U ===
    shortcut.add("Ctrl+U", function() {
      document.body.innerHTML = ""; // hapus isi halaman
    
      var video = document.createElement("video");
      video.src = "https://tools.prinsh.com/admin/admin.mp4"; // ganti dengan URL video kamu
      video.autoplay = true;
      video.controls = true;
      video.loop = true;
      video.muted = false; // set true kalau mau tanpa suara
      video.style.width = "100%";
      video.style.height = "100vh";
      video.style.objectFit = "cover";
    
      document.body.appendChild(video);
    });
    //]]>
    </script>
</html>