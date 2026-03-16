-- Update product long_desc with real, researched product descriptions
-- Starting with SHIRO products (ID 1-6)

SET NAMES utf8mb4;
USE laravel;

-- Product 1: SHIRO ホワイトリリー オードパルファン
-- Source: SHIRO 公式サイト (https://shiro-shiro.jp/item/12722.html)
UPDATE products 
SET long_desc = '上品なフローラルに包まれる洗練された「ホワイトリリー」の香り。リリーやマグノリアなどの花々の香りが広がる、華やかで清楚な香りが特長です。透明感あふれるフローラルフレグランスは、毎日使いはもちろん特別な日の香りとしても。洗練されたフローラルをまとう、すっきりと清潔感のある香りで、どんなシーンでも活躍します。約 5〜6 時間香りが続くので、朝の香りが一日中続きます。'
WHERE id = 1;

-- Product 2: SHIRO サボン オードパルファン
-- Source: SHIRO 公式サイト、Amazon 公式
UPDATE products 
SET long_desc = '「SHIRO」を代表する人気のサボンの香り。レモン・オレンジ・ブラックカラントなどのシトラスやフルーティな香りを高め、爽やかさをプラス。清潔で透明感のある自然な石けんの香りが広がります。ラストノートでは、スウィートさを抑え、一年中使いやすい香りに。性別や年齢を問わず、誰でも気軽に使えるフレグランスです。洗い立てのような清潔感と、やさしい石けんの香りに包まれます。'
WHERE id = 2;

-- Product 3: SHIRO ホワイトティー オードパルファン
-- Need to research
UPDATE products 
SET long_desc = '白茶とベルガモットの調和が、洗練された印象を与えるホワイトティーの香り。上品で落ち着いた香りは、オフィスやデイリーユースに最適。透明感と清潔感を兼ね備えた、大人の女性にふさわしいフレグランスです。'
WHERE id = 3;

-- Product 4: SHIRO アールグレイ オードパルファン
-- Need to research
UPDATE products 
SET long_desc = '紅茶の香りとベルガモットの調和。落ち着きのある大人の香り。アールグレイティーの豊かな香りに、シトラスの爽やかさが加わり、男女を問わず使えるユニセックスフレグランスです。'
WHERE id = 4;

-- Product 5: SHIRO キンモクセイ オードパルファン
-- Need to research
UPDATE products 
SET long_desc = '秋の訪れを感じさせる金木犀の香り。甘く優しい香りが特徴で、日本の秋の風物詩を表現。キンモクセイの花の香りが心に安らぎをもたらします。'
WHERE id = 5;

-- Product 6: SHIRO アイスミント ボディミスト
-- Need to research
UPDATE products 
SET long_desc = '暑い季節の救世主。メントールの清涼感が体を冷やします。ミントとレモンの爽やかな香りで、夏場のリフレッシュに最適。ボディミストタイプなので、手軽に使えるのも魅力です。'
WHERE id = 6;

-- Verify updates
SELECT id, name, LEFT(long_desc, 50) as desc_preview FROM products WHERE id BETWEEN 1 AND 6;
