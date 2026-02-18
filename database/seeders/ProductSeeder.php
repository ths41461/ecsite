<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        if (Product::count() > 0) {
            return;
        }

        $brands = Brand::all()->keyBy('name');
        $categories = Category::all()->keyBy('name');

        $products = $this->getAllProductsData();

        foreach ($products as $productData) {
            $brand = $brands[$productData['brand_name']] ?? null;
            $category = $categories[$productData['category_name']] ?? null;

            if (! $brand || ! $category) {
                continue;
            }

            $product = Product::create([
                'name' => $productData['name'],
                'slug' => Str::slug($productData['name']).'-'.Str::lower(Str::random(6)),
                'brand_id' => $brand->id,
                'category_id' => $category->id,
                'short_desc' => $productData['short_desc'],
                'long_desc' => $productData['long_desc'] ?? $productData['short_desc'],
                'is_active' => true,
                'featured' => $productData['featured'] ?? false,
                'attributes_json' => [
                    'notes' => $productData['notes'],
                    'gender' => $productData['gender'],
                ],
                'meta_json' => ['seo_title' => $productData['name']],
                'published_at' => now(),
            ]);

            $product->categories()->syncWithoutDetaching([$category->id]);

            foreach ($productData['variants'] as $variantData) {
                ProductVariant::create([
                    'product_id' => $product->id,
                    'sku' => $variantData['sku'],
                    'price_yen' => $variantData['price_yen'],
                    'sale_price_yen' => $variantData['sale_price_yen'] ?? null,
                    'option_json' => [
                        'size_ml' => $variantData['size_ml'],
                        'gender' => $productData['gender'],
                        'concentration' => $variantData['concentration'],
                    ],
                    'is_active' => true,
                ]);
            }
        }
    }

    private function getAllProductsData(): array
    {
        return array_merge(
            $this->getShiroProducts(),
            $this->getCosmeDecorteProducts(),
            $this->getIsseyMiyakeProducts(),
            $this->getShiseidoProducts(),
            $this->getHanaeMoriProducts(),
            $this->getKenzoProducts(),
            $this->getAnnaSuiProducts(),
            $this->getPaulJoeProducts(),
            $this->getJillStuartProducts(),
            $this->getExcelProducts(),
            $this->getChanelProducts(),
            $this->getDiorProducts(),
            $this->getTomFordProducts(),
            $this->getGucciProducts(),
            $this->getVersaceProducts(),
            $this->getChloeProducts(),
            $this->getYslProducts(),
            $this->getPradaProducts(),
            $this->getArmaniProducts(),
            $this->getJoMaloneProducts()
        );
    }

    // === SHIRO (6 products) ===
    private function getShiroProducts(): array
    {
        return [
            ['brand_name' => 'SHIRO', 'category_name' => 'フローラル EDP', 'name' => 'ホワイトリリー オードパルファン', 'gender' => 'women', 'short_desc' => '優雅で上品なフローラルの香り。ベルガモットやグリーンのフレッシュなトップノートから始まり、白いユリの花束が広がります。', 'notes' => ['top' => 'ベルガモット、グリーン', 'middle' => 'ホワイトリリー、ジャスミン', 'base' => 'ムスク、シダーウッド'], 'featured' => true, 'variants' => [['sku' => 'SHIRO-WL-040', 'size_ml' => 40, 'price_yen' => 4180, 'concentration' => 'EDP'], ['sku' => 'SHIRO-WL-010', 'size_ml' => 10, 'price_yen' => 1430, 'concentration' => 'EDP']]],
            ['brand_name' => 'SHIRO', 'category_name' => 'フレッシュ EDT', 'name' => 'サボン オードパルファン', 'gender' => 'unisex', 'short_desc' => 'シンプルで爽やかな石鹸系の香り。レモンやオレンジの爽やかさにローズやジャスミンのフローラル感が加わり、清潔感のある香り。', 'notes' => ['top' => 'レモン、オレンジ', 'middle' => 'ローズ、ジャスミン', 'base' => 'ムスク、アンバー'], 'featured' => true, 'variants' => [['sku' => 'SHIRO-SV-040', 'size_ml' => 40, 'price_yen' => 4180, 'concentration' => 'EDP'], ['sku' => 'SHIRO-SV-010', 'size_ml' => 10, 'price_yen' => 1430, 'concentration' => 'EDP']]],
            ['brand_name' => 'SHIRO', 'category_name' => 'フローラル EDP', 'name' => 'ホワイトティー オードパルファン', 'gender' => 'women', 'short_desc' => 'ホワイトティーの優雅な香り。白茶とベルガモットの調和が、洗練された印象を与えます。', 'notes' => ['top' => 'ベルガモット、レモン', 'middle' => 'ホワイトティー、ジャスミン', 'base' => 'ムスク、シダー'], 'variants' => [['sku' => 'SHIRO-WT-040', 'size_ml' => 40, 'price_yen' => 4180, 'concentration' => 'EDP']]],
            ['brand_name' => 'SHIRO', 'category_name' => 'シトラス EDT', 'name' => 'アールグレイ オードパルファン', 'gender' => 'unisex', 'short_desc' => '紅茶の香りとベルガモットの調和。落ち着きのある大人の香り。', 'notes' => ['top' => 'ベルガモット、レモン', 'middle' => 'アールグレイティー、フローラル', 'base' => 'ムスク、ウッディ'], 'variants' => [['sku' => 'SHIRO-EG-040', 'size_ml' => 40, 'price_yen' => 4180, 'concentration' => 'EDP']]],
            ['brand_name' => 'SHIRO', 'category_name' => 'フローラル EDP', 'name' => 'キンモクセイ オードパルファン', 'gender' => 'women', 'short_desc' => '秋の訪れを感じさせる金木犀の香り。甘く優しい香りが特徴。', 'notes' => ['top' => 'シトラス、グリーン', 'middle' => 'キンモクセイ、アプリコット', 'base' => 'ムスク、アンバー'], 'variants' => [['sku' => 'SHIRO-KM-040', 'size_ml' => 40, 'price_yen' => 4180, 'concentration' => 'EDP']]],
            ['brand_name' => 'SHIRO', 'category_name' => 'ボディミスト', 'name' => 'アイスミント ボディミスト', 'gender' => 'unisex', 'short_desc' => '暑い季節の救世主。メントールの清涼感が体を冷やします。', 'notes' => ['top' => 'ミント、レモン', 'middle' => 'ハーブ、フローラル', 'base' => 'ムスク'], 'variants' => [['sku' => 'SHIRO-IM-100', 'size_ml' => 100, 'price_yen' => 2750, 'concentration' => 'Mist']]],
        ];
    }

    // === COSME DECORTE (6 products) ===
    private function getCosmeDecorteProducts(): array
    {
        return [
            ['brand_name' => 'COSME DECORTE', 'category_name' => 'シトラス EDT', 'name' => 'キモノ ユイ オードトワレ', 'gender' => 'women', 'short_desc' => '和情緒あふれるシトラスフローラル。爽やかでほろ苦い日本のカンキツと軽快なピンクペッパーが弾けるトップノート。', 'notes' => ['top' => '日本のカンキツ、ピンクペッパー', 'middle' => 'オレンジフラワー', 'base' => 'バニラ'], 'featured' => true, 'variants' => [['sku' => 'CD-KIM-YUI-50', 'size_ml' => 50, 'price_yen' => 7700, 'concentration' => 'EDT'], ['sku' => 'CD-KIM-YUI-15', 'size_ml' => 15, 'price_yen' => 3520, 'concentration' => 'EDT']]],
            ['brand_name' => 'COSME DECORTE', 'category_name' => 'フローラル EDP', 'name' => 'キモノ ツヤ オードトワレ', 'gender' => 'women', 'short_desc' => 'りんごとピオニー、モクレンの優雅な香り。', 'notes' => ['top' => 'りんご、カシス', 'middle' => 'ピオニー、モクレン', 'base' => 'シダーウッド'], 'variants' => [['sku' => 'CD-KIM-TSU-50', 'size_ml' => 50, 'price_yen' => 7700, 'concentration' => 'EDT']]],
            ['brand_name' => 'COSME DECORTE', 'category_name' => 'ウッディ EDP', 'name' => 'キモノ キヒン オードトワレ', 'gender' => 'women', 'short_desc' => '梅、リリー、スミレの凛とした香り。', 'notes' => ['top' => 'プラム、梅', 'middle' => 'リリー、スミレ', 'base' => 'ムスク'], 'variants' => [['sku' => 'CD-KIM-KIH-50', 'size_ml' => 50, 'price_yen' => 7700, 'concentration' => 'EDT']]],
            ['brand_name' => 'COSME DECORTE', 'category_name' => 'フローラル EDP', 'name' => 'キモノ ウララ オードトワレ', 'gender' => 'women', 'short_desc' => '蓮、ローズ、スズランの透明感ある香り。', 'notes' => ['top' => '蓮、フローラル', 'middle' => 'ローズ、スズラン', 'base' => 'ムスク'], 'variants' => [['sku' => 'CD-KIM-URA-50', 'size_ml' => 50, 'price_yen' => 7700, 'concentration' => 'EDT']]],
            ['brand_name' => 'COSME DECORTE', 'category_name' => 'フローラル EDP', 'name' => 'キモノ リン オードトワレ', 'gender' => 'women', 'short_desc' => 'アイリス、スミレ、シダーの上品な香り。', 'notes' => ['top' => 'アイリス、フローラル', 'middle' => 'スミレ', 'base' => 'シダー、アンバー'], 'variants' => [['sku' => 'CD-KIM-RIN-50', 'size_ml' => 50, 'price_yen' => 7700, 'concentration' => 'EDT']]],
            ['brand_name' => 'COSME DECORTE', 'category_name' => 'フローラル EDP', 'name' => 'AQ オードパルファン', 'gender' => 'women', 'short_desc' => '金香木と純白の花々によるフレッシュフローラルムスキー。スキンケア効果もある香水。', 'notes' => ['top' => '金香木、シトラス', 'middle' => '白い花々', 'base' => 'ムスク'], 'variants' => [['sku' => 'CD-AQ-100', 'size_ml' => 100, 'price_yen' => 22000, 'concentration' => 'EDP']]],
        ];
    }

    // === ISSEY MIYAKE (6 products) ===
    private function getIsseyMiyakeProducts(): array
    {
        return [
            ['brand_name' => 'Issey Miyake', 'category_name' => 'フレッシュ EDT', 'name' => 'ロードゥ イッセイ オードトワレ', 'gender' => 'women', 'short_desc' => 'アクアティックフローラルの先駆け。澄んだ透明感と気品を宿す不朽の名香。', 'notes' => ['top' => 'シトラス、フローラル', 'middle' => 'アクアティックノート、ホワイトフラワー', 'base' => 'ムスク、シダー'], 'featured' => true, 'variants' => [['sku' => 'IM-LDI-EDT-50', 'size_ml' => 50, 'price_yen' => 9350, 'concentration' => 'EDT'], ['sku' => 'IM-LDI-EDT-100', 'size_ml' => 100, 'price_yen' => 14300, 'concentration' => 'EDT']]],
            ['brand_name' => 'Issey Miyake', 'category_name' => 'フローラル EDP', 'name' => 'ロードゥ イッセイ オードパルファム インテンス', 'gender' => 'women', 'short_desc' => '海の神秘と豊かさを表現。ポシドニアのグリーンアコードとイランイランの甘美さ。', 'notes' => ['top' => 'ポシドニア、グリーンアコード', 'middle' => 'イランイラン、フローラル', 'base' => 'バニラ、ウッディ'], 'variants' => [['sku' => 'IM-LDI-EDPI-30', 'size_ml' => 30, 'price_yen' => 12320, 'concentration' => 'EDP'], ['sku' => 'IM-LDI-EDPI-50', 'size_ml' => 50, 'price_yen' => 17270, 'concentration' => 'EDP'], ['sku' => 'IM-LDI-EDPI-100', 'size_ml' => 100, 'price_yen' => 24200, 'concentration' => 'EDP']]],
            ['brand_name' => 'Issey Miyake', 'category_name' => 'フレッシュ EDT', 'name' => 'ロードゥ イッセイ プールオム オードトワレ', 'gender' => 'men', 'short_desc' => 'みずみずしい柑橘とウッディの調和。男性らしい清涼感。', 'notes' => ['top' => 'ユズ、シトラス', 'middle' => 'スパイス、ウォータリーノート', 'base' => 'サンダルウッド、ムスク'], 'variants' => [['sku' => 'IM-LDIPH-EDT-40', 'size_ml' => 40, 'price_yen' => 8250, 'concentration' => 'EDT'], ['sku' => 'IM-LDIPH-EDT-75', 'size_ml' => 75, 'price_yen' => 12430, 'concentration' => 'EDT'], ['sku' => 'IM-LDIPH-EDT-125', 'size_ml' => 125, 'price_yen' => 17270, 'concentration' => 'EDT']]],
            ['brand_name' => 'Issey Miyake', 'category_name' => 'ウッディ EDP', 'name' => 'ロードゥ イッセイ プールオム オードパルファム', 'gender' => 'men', 'short_desc' => '海からインスパイアされた豊かな香り。深みのあるウッディアロマ。', 'notes' => ['top' => 'シトラス、スパイス', 'middle' => 'ウッディノート、ラベンダー', 'base' => 'パチュリ、サンダルウッド'], 'variants' => [['sku' => 'IM-LDIPH-EDP-40', 'size_ml' => 40, 'price_yen' => 9570, 'concentration' => 'EDP'], ['sku' => 'IM-LDIPH-EDP-75', 'size_ml' => 75, 'price_yen' => 12430, 'concentration' => 'EDP']]],
            ['brand_name' => 'Issey Miyake', 'category_name' => 'フローラル EDP', 'name' => 'ロードゥ イッセイ ピュア オードトワレ', 'gender' => 'women', 'short_desc' => '純粋でフレッシュな水の香り。ムスキーアクアティックフローラル。', 'notes' => ['top' => 'シトラス、マリン', 'middle' => 'フローラル、アクアティック', 'base' => 'ムスク、シダー'], 'variants' => [['sku' => 'IM-LDIP-EDT-50', 'size_ml' => 50, 'price_yen' => 8800, 'concentration' => 'EDT']]],
            ['brand_name' => 'Issey Miyake', 'category_name' => 'フローラル EDP', 'name' => 'ロードゥ イッセイ ソーラーバイオレット', 'gender' => 'women', 'short_desc' => '華やかなバイオレットの香り。太陽の光を浴びた花々のブーケ。', 'notes' => ['top' => 'シトラス、グリーン', 'middle' => 'バイオレット、アイリス', 'base' => 'ムスク、ウッディ'], 'variants' => [['sku' => 'IM-LDI-SV-50', 'size_ml' => 50, 'price_yen' => 9900, 'concentration' => 'EDT']]],
        ];
    }

    // === SHISEIDO (6 products) ===
    private function getShiseidoProducts(): array
    {
        return [
            ['brand_name' => '資生堂', 'category_name' => 'オリエンタル EDP', 'name' => 'SHISEIDO ZEN オードパルファム', 'gender' => 'women', 'short_desc' => 'みずみずしく、強く、甘く。今を生きる女性たちへおくる、歓びを呼び覚ます香水。', 'notes' => ['top' => 'フルーツ、シトラス', 'middle' => 'フローラル、アンバー', 'base' => 'ウッド、パチュリ'], 'featured' => true, 'variants' => [['sku' => 'SHI-ZEN-30', 'size_ml' => 30, 'price_yen' => 5500, 'concentration' => 'EDP'], ['sku' => 'SHI-ZEN-50', 'size_ml' => 50, 'price_yen' => 8250, 'concentration' => 'EDP']]],
            ['brand_name' => '資生堂', 'category_name' => 'フローラル EDP', 'name' => 'SHISEIDO ギンザ オードパルファム', 'gender' => 'women', 'short_desc' => '女性の秘めた美しさを表現した香り。銀座のエッセンスを凝縮。', 'notes' => ['top' => 'シトラス、フローラル', 'middle' => 'ジャスミン、ローズ', 'base' => 'ムスク、サンダルウッド'], 'variants' => [['sku' => 'SHI-GIN-30', 'size_ml' => 30, 'price_yen' => 9350, 'concentration' => 'EDP'], ['sku' => 'SHI-GIN-50', 'size_ml' => 50, 'price_yen' => 13750, 'concentration' => 'EDP'], ['sku' => 'SHI-GIN-90', 'size_ml' => 90, 'price_yen' => 18700, 'concentration' => 'EDP']]],
            ['brand_name' => '資生堂', 'category_name' => 'ウッディ EDP', 'name' => 'BAUM ウッドランドウィンズ オーデコロン', 'gender' => 'unisex', 'short_desc' => '湖畔の林に吹く風のような、清々しい香り。森林浴のような癒し。', 'notes' => ['top' => 'ベルガモット、カモミール', 'middle' => 'サイプレス、コリアンダー、ゼラニウム、ローズ', 'base' => 'シダーウッド、ベチバー'], 'variants' => [['sku' => 'SHI-BM-WW-60', 'size_ml' => 60, 'price_yen' => 14850, 'concentration' => 'EDC']]],
            ['brand_name' => '資生堂', 'category_name' => 'ウッディ EDP', 'name' => 'BAUM フォレストエンブレイス オーデコロン', 'gender' => 'unisex', 'short_desc' => '深い静寂の森で、瞑想する香り。スモーキーで神秘的なハーモニー。', 'notes' => ['top' => 'カルダモン、ライム', 'middle' => 'イランイラン、ジャスミン、ローズマリー', 'base' => 'シダーウッド、パチュリ'], 'variants' => [['sku' => 'SHI-BM-FE-60', 'size_ml' => 60, 'price_yen' => 14850, 'concentration' => 'EDC']]],
            ['brand_name' => '資生堂', 'category_name' => 'フローラル EDP', 'name' => 'マジョリカマジョルカ マジョロマンティカ', 'gender' => 'women', 'short_desc' => 'とろ～り一滴で「女の子」の血が騒ぎ出す、甘い甘い魔法の媚薬。', 'notes' => ['top' => 'レッドフルーツ、ジューシー', 'middle' => 'フローラルスイート', 'base' => 'スイート、ミステリアス'], 'variants' => [['sku' => 'MJ-MM-20', 'size_ml' => 20, 'price_yen' => 1760, 'concentration' => 'EDT']]],
            ['brand_name' => '資生堂', 'category_name' => 'フレッシュ EDT', 'name' => 'SHISEIDO メン オードトワレ', 'gender' => 'men', 'short_desc' => '資生堂の男性用フレグランス。清潔感と落ち着きのある香り。', 'notes' => ['top' => 'シトラス、グリーン', 'middle' => 'スパイス、フローラル', 'base' => 'ウッディ、ムスク'], 'variants' => [['sku' => 'SHI-MEN-50', 'size_ml' => 50, 'price_yen' => 6050, 'concentration' => 'EDT']]],
        ];
    }

    // === HANAE MORI (6 products) ===
    private function getHanaeMoriProducts(): array
    {
        return [
            ['brand_name' => 'ハナエモリ', 'category_name' => 'フローラル EDP', 'name' => 'バタフライ オードパルファム', 'gender' => 'women', 'short_desc' => '森英恵を象徴する蝶のモチーフ。甘く官能的なフローラルの香り。', 'notes' => ['top' => 'レッドフルーツ、ストロベリー', 'middle' => 'ピンクピオニー、アーモンドブロッサム', 'base' => 'アーモンドウッド、バニラ'], 'featured' => true, 'variants' => [['sku' => 'HM-BF-EDP-50', 'size_ml' => 50, 'price_yen' => 8800, 'concentration' => 'EDP'], ['sku' => 'HM-BF-EDP-100', 'size_ml' => 100, 'price_yen' => 12100, 'concentration' => 'EDP']]],
            ['brand_name' => 'ハナエモリ', 'category_name' => 'フローラル EDP', 'name' => 'バタフライ オードトワレ', 'gender' => 'women', 'short_desc' => '春の渦巻く蝶に敬意を表した、フルーティで官能的な香り。', 'notes' => ['top' => 'ブラックカラント、ストロベリー', 'middle' => 'ピオニー、ローズ', 'base' => 'アーモンド、バニラ'], 'variants' => [['sku' => 'HM-BF-EDT-50', 'size_ml' => 50, 'price_yen' => 6600, 'concentration' => 'EDT']]],
            ['brand_name' => 'ハナエモリ', 'category_name' => 'フローラル EDP', 'name' => 'パープルバタフライ オードパルファム', 'gender' => 'women', 'short_desc' => '神秘的でエレガントなパープルの世界。深みのあるフローラル。', 'notes' => ['top' => 'フルーツ、シトラス', 'middle' => 'フローラル、スミレ', 'base' => 'ムスク、ウッディ'], 'variants' => [['sku' => 'HM-PB-50', 'size_ml' => 50, 'price_yen' => 8800, 'concentration' => 'EDP']]],
            ['brand_name' => 'ハナエモリ', 'category_name' => 'ウッディ EDP', 'name' => 'HM オードパルファム', 'gender' => 'women', 'short_desc' => 'シンプルで洗練された香り。モダンなウッディフローラル。', 'notes' => ['top' => 'シトラス、フルーツ', 'middle' => 'フローラル、スパイス', 'base' => 'ウッディ、ムスク'], 'variants' => [['sku' => 'HM-HM-50', 'size_ml' => 50, 'price_yen' => 7700, 'concentration' => 'EDP']]],
            ['brand_name' => 'ハナエモリ', 'category_name' => 'フローラル EDP', 'name' => 'ハナエ オードパルファム', 'gender' => 'women', 'short_desc' => '東洋と西洋の融合を表現した香り。', 'notes' => ['top' => 'シトラス、グリーン', 'middle' => 'フローラル、フルーティ', 'base' => 'ムスク、サンダルウッド'], 'variants' => [['sku' => 'HM-HANA-50', 'size_ml' => 50, 'price_yen' => 7700, 'concentration' => 'EDP']]],
            ['brand_name' => 'ハナエモリ', 'category_name' => 'フレッシュ EDT', 'name' => 'HiM オードトワレ', 'gender' => 'men', 'short_desc' => '男性のためのフレッシュでモダンな香り。', 'notes' => ['top' => 'シトラス、アロマティック', 'middle' => 'スパイス、ウッディ', 'base' => 'ムスク、シダー'], 'variants' => [['sku' => 'HM-HIM-50', 'size_ml' => 50, 'price_yen' => 6600, 'concentration' => 'EDT']]],
        ];
    }

    // === KENZO (6 products) ===
    private function getKenzoProducts(): array
    {
        return [
            ['brand_name' => 'ケンゾー', 'category_name' => 'フローラル EDP', 'name' => 'フラワー バイ ケンゾー オードパルファム', 'gender' => 'women', 'short_desc' => '一輪のポピーが咲き誇るような、シンプルで力強いフローラルの香り。', 'notes' => ['top' => 'マンダリン、ブラックカラント', 'middle' => 'ポピー、ローズ、ジャスミン', 'base' => 'バニラ、ムスク、インセンス'], 'featured' => true, 'variants' => [['sku' => 'KEN-FLW-EDP-30', 'size_ml' => 30, 'price_yen' => 6270, 'concentration' => 'EDP'], ['sku' => 'KEN-FLW-EDP-50', 'size_ml' => 50, 'price_yen' => 8250, 'concentration' => 'EDP']]],
            ['brand_name' => 'ケンゾー', 'category_name' => 'フローラル EDP', 'name' => 'フラワー バイ ケンゾー オードトワレ', 'gender' => 'women', 'short_desc' => '軽やかでフレッシュなポピーの香り。日常使いにぴったり。', 'notes' => ['top' => 'マンダリン、カシス', 'middle' => 'ポピー、ジャスミン', 'base' => 'ムスク、バニラ'], 'variants' => [['sku' => 'KEN-FLW-EDT-30', 'size_ml' => 30, 'price_yen' => 5610, 'concentration' => 'EDT'], ['sku' => 'KEN-FLW-EDT-50', 'size_ml' => 50, 'price_yen' => 7150, 'concentration' => 'EDT']]],
            ['brand_name' => 'ケンゾー', 'category_name' => 'フレッシュ EDT', 'name' => 'ローパ ケンゾー プールオム オードトワレ', 'gender' => 'men', 'short_desc' => '水のエッセンスを凝縮したフレッシュな香り。', 'notes' => ['top' => 'ユズ、レモン', 'middle' => 'ミント、アクアティック', 'base' => 'サンダルウッド、ムスク'], 'variants' => [['sku' => 'KEN-LEAU-30', 'size_ml' => 30, 'price_yen' => 4840, 'concentration' => 'EDT'], ['sku' => 'KEN-LEAU-50', 'size_ml' => 50, 'price_yen' => 6270, 'concentration' => 'EDT'], ['sku' => 'KEN-LEAU-100', 'size_ml' => 100, 'price_yen' => 8800, 'concentration' => 'EDT']]],
            ['brand_name' => 'ケンゾー', 'category_name' => 'フレッシュ EDT', 'name' => 'アクア ケンゾー プールオム オードトワレ', 'gender' => 'men', 'short_desc' => '深い海のエネルギーを表現したアクアティックフレグランス。', 'notes' => ['top' => 'シトラス、ユズ', 'middle' => 'アクアティックノート、ミント', 'base' => 'シダー、ムスク'], 'variants' => [['sku' => 'KEN-AQ-30', 'size_ml' => 30, 'price_yen' => 4950, 'concentration' => 'EDT'], ['sku' => 'KEN-AQ-50', 'size_ml' => 50, 'price_yen' => 6490, 'concentration' => 'EDT']]],
            ['brand_name' => 'ケンゾー', 'category_name' => 'フローラル EDP', 'name' => 'ケンゾー ワールド オードパルファム', 'gender' => 'women', 'short_desc' => '世界を舞台にした自由で楽しい香り。', 'notes' => ['top' => 'ペア、フローラル', 'middle' => 'ピオニー、ジャスミン', 'base' => 'アンバー、ムスク'], 'variants' => [['sku' => 'KEN-WRLD-30', 'size_ml' => 30, 'price_yen' => 5830, 'concentration' => 'EDP'], ['sku' => 'KEN-WRLD-50', 'size_ml' => 50, 'price_yen' => 7700, 'concentration' => 'EDP']]],
            ['brand_name' => 'ケンゾー', 'category_name' => 'フローラル EDP', 'name' => 'フラワー エア オードパルファム', 'gender' => 'women', 'short_desc' => '軽やかに舞い上がるポピーの香り。エアリーでモダンな印象。', 'notes' => ['top' => 'グリーンアップル、カシス', 'middle' => 'ポピー、ローズ、フリージア', 'base' => 'ムスク、サンダルウッド'], 'variants' => [['sku' => 'KEN-FAIR-30', 'size_ml' => 30, 'price_yen' => 7920, 'concentration' => 'EDP']]],
        ];
    }

    // === ANNA SUI (6 products) ===
    private function getAnnaSuiProducts(): array
    {
        return [
            ['brand_name' => 'アナスイ', 'category_name' => 'フルーティー EDT', 'name' => 'シークレットウィッシュ オードトワレ', 'gender' => 'women', 'short_desc' => '「願うことが願いを叶える」をテーマにした、フルーティーフローラルムスキーの香り。', 'notes' => ['top' => 'レモン、メロン、タジェット', 'middle' => 'ブラックカラント、パイナップル', 'base' => 'ホワイトシダー、アンバー、スキンムスク'], 'featured' => true, 'variants' => [['sku' => 'AS-SW-30', 'size_ml' => 30, 'price_yen' => 5610, 'concentration' => 'EDT'], ['sku' => 'AS-SW-75', 'size_ml' => 75, 'price_yen' => 10120, 'concentration' => 'EDT']]],
            ['brand_name' => 'アナスイ', 'category_name' => 'フルーティー EDT', 'name' => 'ラッキーウィッシュ オードトワレ', 'gender' => 'women', 'short_desc' => '幸せを呼び込むフレッシュでフルーティな香り。', 'notes' => ['top' => 'シトラス、ベリー', 'middle' => 'フローラル、フルーティ', 'base' => 'ムスク、ウッディ'], 'variants' => [['sku' => 'AS-LW-30', 'size_ml' => 30, 'price_yen' => 5610, 'concentration' => 'EDT']]],
            ['brand_name' => 'アナスイ', 'category_name' => 'フローラル EDT', 'name' => 'ファンタジア オードトワレ', 'gender' => 'women', 'short_desc' => 'ユニコーンが舞うファンタジックな世界。甘くロマンティックな香り。', 'notes' => ['top' => 'ピンクペッパー、柚子', 'middle' => 'バラのプリザーブ、ラズベリー', 'base' => 'ヒノキ、ゴールデンアンバー'], 'variants' => [['sku' => 'AS-FAN-30', 'size_ml' => 30, 'price_yen' => 5830, 'concentration' => 'EDT'], ['sku' => 'AS-FAN-75', 'size_ml' => 75, 'price_yen' => 10450, 'concentration' => 'EDT']]],
            ['brand_name' => 'アナスイ', 'category_name' => 'フローラル EDT', 'name' => 'ファンタジア マーメイド オードトワレ', 'gender' => 'women', 'short_desc' => 'マーメイドの世界観を表現した、マリンでフルーティな香り。', 'notes' => ['top' => 'シトラス、ブラッドオレンジ', 'middle' => 'スイートピー、ジャスミン', 'base' => 'バニラ、ウッディ'], 'variants' => [['sku' => 'AS-FANM-30', 'size_ml' => 30, 'price_yen' => 5830, 'concentration' => 'EDT']]],
            ['brand_name' => 'アナスイ', 'category_name' => 'フローラル EDT', 'name' => 'スイドリームス オードトワレ', 'gender' => 'women', 'short_desc' => '夢見る少女のための、甘く幻想的な香り。', 'notes' => ['top' => 'フルーティ、シトラス', 'middle' => 'フローラル、スウィート', 'base' => 'ムスク、バニラ'], 'variants' => [['sku' => 'AS-SD-30', 'size_ml' => 30, 'price_yen' => 5830, 'concentration' => 'EDT']]],
            ['brand_name' => 'アナスイ', 'category_name' => 'フローラル EDT', 'name' => 'フライト オブ ファンシー オードトワレ', 'gender' => 'women', 'short_desc' => '自由な発想と冒険を表現した、エキゾチックな香り。', 'notes' => ['top' => 'ライチ、ユズ', 'middle' => 'マグノリア、フリージア', 'base' => 'アンバー、ムスク'], 'variants' => [['sku' => 'AS-FOF-30', 'size_ml' => 30, 'price_yen' => 5500, 'concentration' => 'EDT']]],
        ];
    }

    // === PAUL & JOE (6 products) ===
    private function getPaulJoeProducts(): array
    {
        return [
            ['brand_name' => 'ポール＆ジョー', 'category_name' => 'フローラル EDT', 'name' => 'ブルー オードトワレ', 'gender' => 'women', 'short_desc' => 'デザイナー・ソフィーの愛猫ジプシーをイメージした、フレッシュでクリーンな香り。', 'notes' => ['top' => 'シトラス、グリーンノート', 'middle' => 'フローラル、フリージア', 'base' => 'ムスク、シダー'], 'variants' => [['sku' => 'PJ-BLUE-50', 'size_ml' => 50, 'price_yen' => 6600, 'concentration' => 'EDT']]],
            ['brand_name' => 'ポール＆ジョー', 'category_name' => 'フローラル EDT', 'name' => 'ブラン オードトワレ', 'gender' => 'women', 'short_desc' => '純白で優しいイメージのフローラルムスキー。', 'notes' => ['top' => 'シトラス、ホワイトティー', 'middle' => 'ジャスミン、ホワイトローズ', 'base' => 'ホワイトムスク、サンダルウッド'], 'variants' => [['sku' => 'PJ-BLANC-50', 'size_ml' => 50, 'price_yen' => 6600, 'concentration' => 'EDT']]],
            ['brand_name' => 'ポール＆ジョー', 'category_name' => 'フローラル EDT', 'name' => 'ローズ オードトワレ', 'gender' => 'women', 'short_desc' => '華やかでロマンティックなバラの香り。', 'notes' => ['top' => 'ピンクペッパー、ベリー', 'middle' => 'ダマスクローズ、ピオニー', 'base' => 'アンバー、ムスク'], 'variants' => [['sku' => 'PJ-ROSE-50', 'size_ml' => 50, 'price_yen' => 6600, 'concentration' => 'EDT']]],
            ['brand_name' => 'ポール＆ジョー', 'category_name' => 'ボディミスト', 'name' => 'リフレッシング ミスト', 'gender' => 'women', 'short_desc' => 'さっぱりとした使用感のボディミスト。', 'notes' => ['top' => 'シトラス、ミント', 'middle' => 'フローラル、グリーン', 'base' => 'ムスク'], 'variants' => [['sku' => 'PJ-MIST-100', 'size_ml' => 100, 'price_yen' => 3850, 'concentration' => 'Mist']]],
            ['brand_name' => 'ポール＆ジョー', 'category_name' => 'ヘアミスト', 'name' => 'ヘア＆ボディ ミスト', 'gender' => 'women', 'short_desc' => '髪にも使えるフレグランスミスト。', 'notes' => ['top' => 'シトラス、フルーティ', 'middle' => 'フローラル', 'base' => 'ムスク'], 'variants' => [['sku' => 'PJ-HM-100', 'size_ml' => 100, 'price_yen' => 4400, 'concentration' => 'Mist']]],
            ['brand_name' => 'ポール＆ジョー', 'category_name' => 'フローラル EDT', 'name' => 'オレンジブロッサム オードトワレ', 'gender' => 'women', 'short_desc' => 'オレンジの花の爽やかで甘い香り。', 'notes' => ['top' => 'オレンジ、ネロリ', 'middle' => 'オレンジブロッサム、ジャスミン', 'base' => 'ムスク、バニラ'], 'variants' => [['sku' => 'PJ-OB-50', 'size_ml' => 50, 'price_yen' => 6600, 'concentration' => 'EDT']]],
        ];
    }

    // === JILL STUART (6 products) ===
    private function getJillStuartProducts(): array
    {
        return [
            ['brand_name' => 'ジルスチュアート', 'category_name' => 'フローラル EDP', 'name' => 'ホワイトフローラル オードトワレ', 'gender' => 'women', 'short_desc' => '純白の花束のようなピュアでロマンティックな香り。', 'notes' => ['top' => 'ピーチ、ストロベリー', 'middle' => 'リリー、ミュゲ', 'base' => 'ホワイトムスク、サンダルウッド'], 'featured' => true, 'variants' => [['sku' => 'JS-WF-10', 'size_ml' => 10, 'price_yen' => 2200, 'concentration' => 'EDT'], ['sku' => 'JS-WF-50', 'size_ml' => 50, 'price_yen' => 4400, 'concentration' => 'EDT']]],
            ['brand_name' => 'ジルスチュアート', 'category_name' => 'フローラル EDP', 'name' => 'クリスタルブルーム オードパルファン', 'gender' => 'women', 'short_desc' => 'クリスタルのように輝く花々のブーケ。華やかで上品な香り。', 'notes' => ['top' => 'シトラス、フルーツ', 'middle' => 'ローズ、ジャスミン、フリージア', 'base' => 'ムスク、サンダルウッド'], 'variants' => [['sku' => 'JS-CB-30', 'size_ml' => 30, 'price_yen' => 6490, 'concentration' => 'EDP']]],
            ['brand_name' => 'ジルスチュアート', 'category_name' => 'フローラル EDP', 'name' => 'ブリリアントジュエル オードパルファン', 'gender' => 'women', 'short_desc' => '宝石のように輝く女性のためのフレグランス。', 'notes' => ['top' => 'ベリー、シトラス', 'middle' => 'ローズ、ジャスミン', 'base' => 'アンバー、ムスク'], 'variants' => [['sku' => 'JS-BJ-30', 'size_ml' => 30, 'price_yen' => 6490, 'concentration' => 'EDP'], ['sku' => 'JS-BJ-50', 'size_ml' => 50, 'price_yen' => 9130, 'concentration' => 'EDP']]],
            ['brand_name' => 'ジルスチュアート', 'category_name' => 'フローラル EDP', 'name' => 'サクラブーケ オードパルファン', 'gender' => 'women', 'short_desc' => '春の桜の花束をイメージした限定フレグランス。', 'notes' => ['top' => 'サクラ、フルーツ', 'middle' => 'ローズ、ジャスミン', 'base' => 'ムスク、サンダルウッド'], 'variants' => [['sku' => 'JS-SAK-30', 'size_ml' => 30, 'price_yen' => 6490, 'concentration' => 'EDP']]],
            ['brand_name' => 'ジルスチュアート', 'category_name' => 'ヘアミスト', 'name' => 'ホワイトフローラル ヘアミスト', 'gender' => 'women', 'short_desc' => '髪に香りを纏うヘアミスト。', 'notes' => ['top' => 'ピーチ、ベリー', 'middle' => 'ホワイトフローラル', 'base' => 'ムスク'], 'variants' => [['sku' => 'JS-WF-HM-200', 'size_ml' => 200, 'price_yen' => 3080, 'concentration' => 'Mist']]],
            ['brand_name' => 'ジルスチュアート', 'category_name' => 'フローラル EDP', 'name' => 'ピーチーホワイトフローラル オードトワレ', 'gender' => 'women', 'short_desc' => 'みずみずしく甘いピーチと白い花の香り。限定品。', 'notes' => ['top' => 'ピーチ、ベリー', 'middle' => 'ホワイトフローラル', 'base' => 'ムスク、バニラ'], 'variants' => [['sku' => 'JS-PWF-50', 'size_ml' => 50, 'price_yen' => 4400, 'concentration' => 'EDT']]],
        ];
    }

    // === EXCEL (6 products) ===
    private function getExcelProducts(): array
    {
        return [
            ['brand_name' => 'エクセル', 'category_name' => 'ボディミスト', 'name' => 'フレグランスボディミスト ホワイトティー', 'gender' => 'women', 'short_desc' => 'プチプラながら高品質なボディミスト。白茶の香り。', 'notes' => ['top' => 'シトラス、グリーン', 'middle' => 'ホワイトティー、フローラル', 'base' => 'ムスク'], 'variants' => [['sku' => 'EX-WTM-100', 'size_ml' => 100, 'price_yen' => 1430, 'concentration' => 'Mist']]],
            ['brand_name' => 'エクセル', 'category_name' => 'ボディミスト', 'name' => 'フレグランスボディミスト サボン', 'gender' => 'unisex', 'short_desc' => '清潔感のある石鹸の香り。', 'notes' => ['top' => 'レモン、オレンジ', 'middle' => 'ローズ、フローラル', 'base' => 'ムスク'], 'variants' => [['sku' => 'EX-SVM-100', 'size_ml' => 100, 'price_yen' => 1430, 'concentration' => 'Mist']]],
            ['brand_name' => 'エクセル', 'category_name' => 'ボディミスト', 'name' => 'フレグランスヘアミスト', 'gender' => 'women', 'short_desc' => '髪に使えるフレグランスミスト。', 'notes' => ['top' => 'フルーティ、シトラス', 'middle' => 'フローラル', 'base' => 'ムスク'], 'variants' => [['sku' => 'EX-HM-80', 'size_ml' => 80, 'price_yen' => 1540, 'concentration' => 'Mist']]],
            ['brand_name' => 'エクセル', 'category_name' => 'パルファムオイル', 'name' => 'フレグランスオイル ローズ', 'gender' => 'women', 'short_desc' => '練り香水タイプのフレグランスオイル。', 'notes' => ['top' => 'フルーツ', 'middle' => 'ローズ', 'base' => 'ムスク'], 'variants' => [['sku' => 'EX-OIL-10', 'size_ml' => 10, 'price_yen' => 1100, 'concentration' => 'Oil']]],
            ['brand_name' => 'エクセル', 'category_name' => 'パルファムオイル', 'name' => 'フレグランスオイル ジャスミン', 'gender' => 'women', 'short_desc' => '練り香水タイプのフレグランスオイル。', 'notes' => ['top' => 'シトラス', 'middle' => 'ジャスミン', 'base' => 'ムスク'], 'variants' => [['sku' => 'EX-OIL-J-10', 'size_ml' => 10, 'price_yen' => 1100, 'concentration' => 'Oil']]],
            ['brand_name' => 'エクセル', 'category_name' => 'ボディミスト', 'name' => 'フレグランスボディミスト ベリー', 'gender' => 'women', 'short_desc' => '甘いベリーの香り。', 'notes' => ['top' => 'ストロベリー、ラズベリー', 'middle' => 'フローラル', 'base' => 'バニラ'], 'variants' => [['sku' => 'EX-BM-100', 'size_ml' => 100, 'price_yen' => 1430, 'concentration' => 'Mist']]],
        ];
    }

    // === CHANEL (6 products) ===
    private function getChanelProducts(): array
    {
        return [
            ['brand_name' => 'シャネル', 'category_name' => 'フローラル EDP', 'name' => 'シャネル N°5 オードパルファム', 'gender' => 'women', 'short_desc' => '1921年に誕生した永遠の名作。アルデヒドとフラワーの調和が生み出す不朽のフレグランス。', 'notes' => ['top' => 'アルデヒド、イランイラン', 'middle' => 'ジャスミン、ローズ', 'base' => 'サンダルウッド、バニラ'], 'featured' => true, 'variants' => [['sku' => 'CH-N5-EDP-35', 'size_ml' => 35, 'price_yen' => 13200, 'concentration' => 'EDP'], ['sku' => 'CH-N5-EDP-50', 'size_ml' => 50, 'price_yen' => 18700, 'concentration' => 'EDP'], ['sku' => 'CH-N5-EDP-100', 'size_ml' => 100, 'price_yen' => 26400, 'concentration' => 'EDP']]],
            ['brand_name' => 'シャネル', 'category_name' => 'フレッシュ EDT', 'name' => 'シャネル N°5 ロー オードトワレ', 'gender' => 'women', 'short_desc' => 'N°5のモダンな解釈。軽やかでフレッシュな印象。', 'notes' => ['top' => 'シトラス、アルデヒド', 'middle' => 'ジャスミン、ローズ', 'base' => 'シダー、ムスク'], 'variants' => [['sku' => 'CH-N5L-EDT-50', 'size_ml' => 50, 'price_yen' => 15400, 'concentration' => 'EDT'], ['sku' => 'CH-N5L-EDT-100', 'size_ml' => 100, 'price_yen' => 22000, 'concentration' => 'EDT']]],
            ['brand_name' => 'シャネル', 'category_name' => 'オリエンタル EDP', 'name' => 'ココ マドモアゼル オードパルファム', 'gender' => 'women', 'short_desc' => 'モダンで大胆な女性のためのフレグランス。', 'notes' => ['top' => 'オレンジ、ベルガモット', 'middle' => 'ジャスミン、ローズ', 'base' => 'パチュリ、ベチバー'], 'featured' => true, 'variants' => [['sku' => 'CH-CM-EDP-35', 'size_ml' => 35, 'price_yen' => 14850, 'concentration' => 'EDP'], ['sku' => 'CH-CM-EDP-50', 'size_ml' => 50, 'price_yen' => 20900, 'concentration' => 'EDP'], ['sku' => 'CH-CM-EDP-100', 'size_ml' => 100, 'price_yen' => 29700, 'concentration' => 'EDP']]],
            ['brand_name' => 'シャネル', 'category_name' => 'フローラル EDP', 'name' => 'チャンス オー タンドゥル オードパルファム', 'gender' => 'women', 'short_desc' => 'やわらかくフェミニンな香り。', 'notes' => ['top' => 'グレープフルーツ、キンポウゲ', 'middle' => 'ヒヤシンス、ジャスミン', 'base' => 'アンバー、ムスク'], 'variants' => [['sku' => 'CH-CHT-EDP-50', 'size_ml' => 50, 'price_yen' => 18150, 'concentration' => 'EDP'], ['sku' => 'CH-CHT-EDP-100', 'size_ml' => 100, 'price_yen' => 26400, 'concentration' => 'EDP']]],
            ['brand_name' => 'シャネル', 'category_name' => 'フレッシュ EDT', 'name' => 'ブルー ドゥ シャネル オードトワレ', 'gender' => 'men', 'short_desc' => '自由で野性的な男性のためのフレグランス。', 'notes' => ['top' => 'シトラス、ペッパー', 'middle' => 'ミント、ジャスミン', 'base' => 'シダー、サンダルウッド'], 'featured' => true, 'variants' => [['sku' => 'CH-BDC-EDT-50', 'size_ml' => 50, 'price_yen' => 13200, 'concentration' => 'EDT'], ['sku' => 'CH-BDC-EDT-100', 'size_ml' => 100, 'price_yen' => 18700, 'concentration' => 'EDT'], ['sku' => 'CH-BDC-EDT-150', 'size_ml' => 150, 'price_yen' => 24200, 'concentration' => 'EDT']]],
            ['brand_name' => 'シャネル', 'category_name' => 'ウッディ EDP', 'name' => 'ブルー ドゥ シャネル オードパルファム', 'gender' => 'men', 'short_desc' => 'より深みのあるブルー ドゥ シャネル。', 'notes' => ['top' => 'シトラス、ミント', 'middle' => 'ペッパー、ジャスミン', 'base' => 'インセンス、シダー'], 'variants' => [['sku' => 'CH-BDC-EDP-50', 'size_ml' => 50, 'price_yen' => 15400, 'concentration' => 'EDP'], ['sku' => 'CH-BDC-EDP-100', 'size_ml' => 100, 'price_yen' => 22000, 'concentration' => 'EDP']]],
        ];
    }

    // === DIOR (6 products) ===
    private function getDiorProducts(): array
    {
        return [
            ['brand_name' => 'ディオール', 'category_name' => 'フローラル EDP', 'name' => 'ジャドール オードパルファム', 'gender' => 'women', 'short_desc' => '華やかでフェミニンなフローラルの香り。現代的な女性の象徴。', 'notes' => ['top' => 'ペア、メロン、ベルガモット', 'middle' => 'ジャスミン、ローズ、リリー', 'base' => 'バニラ、ムスク、シダー'], 'featured' => true, 'variants' => [['sku' => 'DI-JAD-30', 'size_ml' => 30, 'price_yen' => 11000, 'concentration' => 'EDP'], ['sku' => 'DI-JAD-50', 'size_ml' => 50, 'price_yen' => 16500, 'concentration' => 'EDP'], ['sku' => 'DI-JAD-100', 'size_ml' => 100, 'price_yen' => 24200, 'concentration' => 'EDP']]],
            ['brand_name' => 'ディオール', 'category_name' => 'フローラル EDP', 'name' => 'ミス ディオール オードパルファム', 'gender' => 'women', 'short_desc' => '優雅でロマンティックなフローラルの香り。', 'notes' => ['top' => 'アイリス、ピオニー', 'middle' => 'スズラン、ローズ', 'base' => 'ムスク、バニラ'], 'featured' => true, 'variants' => [['sku' => 'DI-MD-30', 'size_ml' => 30, 'price_yen' => 11000, 'concentration' => 'EDP'], ['sku' => 'DI-MD-50', 'size_ml' => 50, 'price_yen' => 16500, 'concentration' => 'EDP'], ['sku' => 'DI-MD-100', 'size_ml' => 100, 'price_yen' => 24200, 'concentration' => 'EDP']]],
            ['brand_name' => 'ディオール', 'category_name' => 'フレッシュ EDT', 'name' => 'ソヴァージュ オードトワレ', 'gender' => 'men', 'short_desc' => '野性味あふれる男性のためのフレグランス。', 'notes' => ['top' => 'ベルガモット、ペッパー', 'middle' => 'ラベンダー、パチュリ', 'base' => 'アンブロキサン、シダー'], 'featured' => true, 'variants' => [['sku' => 'DI-SAU-60', 'size_ml' => 60, 'price_yen' => 12100, 'concentration' => 'EDT'], ['sku' => 'DI-SAU-100', 'size_ml' => 100, 'price_yen' => 15400, 'concentration' => 'EDT'], ['sku' => 'DI-SAU-200', 'size_ml' => 200, 'price_yen' => 21450, 'concentration' => 'EDT']]],
            ['brand_name' => 'ディオール', 'category_name' => 'ウッディ EDP', 'name' => 'ソヴァージュ オードパルファム', 'gender' => 'men', 'short_desc' => 'より深みのあるソヴァージュ。', 'notes' => ['top' => 'ベルガモット、ペッパー', 'middle' => 'ラベンダー、バニラ', 'base' => 'ナツメグ、アンバー'], 'variants' => [['sku' => 'DI-SAU-EDP-60', 'size_ml' => 60, 'price_yen' => 14300, 'concentration' => 'EDP'], ['sku' => 'DI-SAU-EDP-100', 'size_ml' => 100, 'price_yen' => 19800, 'concentration' => 'EDP']]],
            ['brand_name' => 'ディオール', 'category_name' => 'オリエンタル EDP', 'name' => 'ソヴァージュ エリクシール', 'gender' => 'men', 'short_desc' => 'ソヴァージュの最も濃厚な表現。', 'notes' => ['top' => 'グレープフルーツ、シナモン', 'middle' => 'ラベンダー、リコリス', 'base' => 'バニラ、サンダルウッド'], 'variants' => [['sku' => 'DI-SAU-EL-60', 'size_ml' => 60, 'price_yen' => 17600, 'concentration' => 'Parfum']]],
            ['brand_name' => 'ディオール', 'category_name' => 'フローラル EDP', 'name' => 'プワゾン ガール オードパルファム', 'gender' => 'women', 'short_desc' => '甘く魅惑的な香り。', 'notes' => ['top' => 'ビターオレンジ、レモン', 'middle' => 'ローズ、オレンジブロッサム', 'base' => 'バニラ、トンカ、アーモンド'], 'variants' => [['sku' => 'DI-PG-30', 'size_ml' => 30, 'price_yen' => 11000, 'concentration' => 'EDP'], ['sku' => 'DI-PG-50', 'size_ml' => 50, 'price_yen' => 15950, 'concentration' => 'EDP']]],
        ];
    }

    // === TOM FORD (6 products) ===
    private function getTomFordProducts(): array
    {
        return [
            ['brand_name' => 'トムフォード', 'category_name' => 'ウッディ EDP', 'name' => 'ウードウッド オードパルファム', 'gender' => 'unisex', 'short_desc' => '高級ウードウッドとアンバーの調和。東洋的な魅力。', 'notes' => ['top' => 'ローズウッド、カルダモン', 'middle' => 'ウード、サンダルウッド', 'base' => 'アンバー、バニラ、トンカ'], 'featured' => true, 'variants' => [['sku' => 'TF-OW-30', 'size_ml' => 30, 'price_yen' => 23100, 'concentration' => 'EDP'], ['sku' => 'TF-OW-50', 'size_ml' => 50, 'price_yen' => 34650, 'concentration' => 'EDP'], ['sku' => 'TF-OW-100', 'size_ml' => 100, 'price_yen' => 49500, 'concentration' => 'EDP']]],
            ['brand_name' => 'トムフォード', 'category_name' => 'オリエンタル EDP', 'name' => 'タバコバニラ オードパルファム', 'gender' => 'unisex', 'short_desc' => 'タバコとバニラの魅惑的な組み合わせ。', 'notes' => ['top' => 'タバコリーフ、ジンジャー', 'middle' => 'トンカ、ココア', 'base' => 'バニラ、ドライフルーツ'], 'variants' => [['sku' => 'TF-TV-50', 'size_ml' => 50, 'price_yen' => 39600, 'concentration' => 'EDP'], ['sku' => 'TF-TV-100', 'size_ml' => 100, 'price_yen' => 58850, 'concentration' => 'EDP']]],
            ['brand_name' => 'トムフォード', 'category_name' => 'フローラル EDP', 'name' => 'ブラックオーキッド オードパルファム', 'gender' => 'women', 'short_desc' => '神秘的で官能的なオーキッドの香り。', 'notes' => ['top' => 'トリュフ、ガーデニア', 'middle' => 'ブラックオーキッド、ジャスミン', 'base' => 'バニラ、パチュリ、サンダルウッド'], 'variants' => [['sku' => 'TF-BO-50', 'size_ml' => 50, 'price_yen' => 19800, 'concentration' => 'EDP'], ['sku' => 'TF-BO-100', 'size_ml' => 100, 'price_yen' => 28600, 'concentration' => 'EDP']]],
            ['brand_name' => 'トムフォード', 'category_name' => 'フルーティー EDP', 'name' => 'ロストチェリー オードパルファム', 'gender' => 'unisex', 'short_desc' => '甘く魅惑的なチェリーの香り。', 'notes' => ['top' => 'チェリー、アーモンド、シナモン', 'middle' => 'ジャスミン、ローズ', 'base' => 'バニラ、トンカ、サンダルウッド'], 'variants' => [['sku' => 'TF-LC-50', 'size_ml' => 50, 'price_yen' => 39600, 'concentration' => 'EDP']]],
            ['brand_name' => 'トムフォード', 'category_name' => 'シトラス EDP', 'name' => 'ネロリポルトフィーノ オードパルファム', 'gender' => 'unisex', 'short_desc' => 'イタリアのリビエラを感じさせる爽やかな香り。', 'notes' => ['top' => 'ベルガモット、マンダリン、レモン', 'middle' => 'ネロリ、オレンジブロッサム', 'base' => 'アンバー、ムスク'], 'variants' => [['sku' => 'TF-NP-50', 'size_ml' => 50, 'price_yen' => 23100, 'concentration' => 'EDP'], ['sku' => 'TF-NP-100', 'size_ml' => 100, 'price_yen' => 34650, 'concentration' => 'EDP']]],
            ['brand_name' => 'トムフォード', 'category_name' => 'レザー EDP', 'name' => 'タスカンレザー オードパルファム', 'gender' => 'unisex', 'short_desc' => 'ラズベリーとレザーの大胆な組み合わせ。', 'notes' => ['top' => 'ラズベリー、サフラン', 'middle' => 'レザー、ジャスミン', 'base' => 'アンバー、ウッディ'], 'variants' => [['sku' => 'TF-TL-50', 'size_ml' => 50, 'price_yen' => 39600, 'concentration' => 'EDP']]],
        ];
    }

    // === GUCCI (6 products) ===
    private function getGucciProducts(): array
    {
        return [
            ['brand_name' => 'グッチ', 'category_name' => 'フローラル EDP', 'name' => 'グッチ ブルーム オードパルファム', 'gender' => 'women', 'short_desc' => '自然の花々が咲き誇る庭園のような香り。', 'notes' => ['top' => 'ジャスミン', 'middle' => 'チュベローズ', 'base' => 'ラングーンクリーパー'], 'featured' => true, 'variants' => [['sku' => 'GU-BLOOM-30', 'size_ml' => 30, 'price_yen' => 8250, 'concentration' => 'EDP'], ['sku' => 'GU-BLOOM-50', 'size_ml' => 50, 'price_yen' => 12100, 'concentration' => 'EDP'], ['sku' => 'GU-BLOOM-100', 'size_ml' => 100, 'price_yen' => 16500, 'concentration' => 'EDP']]],
            ['brand_name' => 'グッチ', 'category_name' => 'フローラル EDP', 'name' => 'グッチ ブルーム プロフーモディフィオーリ', 'gender' => 'women', 'short_desc' => 'より豊かで濃厚なブルーム。', 'notes' => ['top' => 'ジャスミン、チュベローズ', 'middle' => 'イランイラン', 'base' => 'サンダルウッド、オリス'], 'variants' => [['sku' => 'GU-BLOOM-P-50', 'size_ml' => 50, 'price_yen' => 13500, 'concentration' => 'EDP']]],
            ['brand_name' => 'グッチ', 'category_name' => 'フローラル EDT', 'name' => 'グギルティ プールファム オードトワレ', 'gender' => 'women', 'short_desc' => '大胆でモダンな女性のための香り。', 'notes' => ['top' => 'マンダリン、ピンクペッパー', 'middle' => 'ライラック、ゼラニウム', 'base' => 'パチュリ、アンバー'], 'variants' => [['sku' => 'GU-GUILTY-30', 'size_ml' => 30, 'price_yen' => 7700, 'concentration' => 'EDT'], ['sku' => 'GU-GUILTY-50', 'size_ml' => 50, 'price_yen' => 11000, 'concentration' => 'EDT']]],
            ['brand_name' => 'グッチ', 'category_name' => 'フローラル EDT', 'name' => 'グッチ フローラ ゴージャスガーデニア', 'gender' => 'women', 'short_desc' => 'ガーデニアを主役にしたフルーティフローラル。', 'notes' => ['top' => 'レッドベリー、ペア', 'middle' => 'ガーデニア、フランジパニ', 'base' => 'パチュリ、ブラウンシュガー'], 'variants' => [['sku' => 'GU-FLORA-30', 'size_ml' => 30, 'price_yen' => 8250, 'concentration' => 'EDP'], ['sku' => 'GU-FLORA-50', 'size_ml' => 50, 'price_yen' => 12100, 'concentration' => 'EDP']]],
            ['brand_name' => 'グッチ', 'category_name' => 'フレッシュ EDT', 'name' => 'グッチ ギルティ プールオム オードトワレ', 'gender' => 'men', 'short_desc' => '現代的な男性のためのフレグランス。', 'notes' => ['top' => 'ラベンダー、レモン', 'middle' => 'オレンジフラワー', 'base' => 'シダー、パチュリ'], 'variants' => [['sku' => 'GU-GUILTY-M-50', 'size_ml' => 50, 'price_yen' => 9350, 'concentration' => 'EDT'], ['sku' => 'GU-GUILTY-M-90', 'size_ml' => 90, 'price_yen' => 13500, 'concentration' => 'EDT']]],
            ['brand_name' => 'グッチ', 'category_name' => 'ウッディ EDP', 'name' => 'グッチ インテンス ウード', 'gender' => 'unisex', 'short_desc' => 'ウードとスパイスの力強い香り。', 'notes' => ['top' => 'ピンクペッパー、ベルガモット', 'middle' => 'セダー、ガルバナム', 'base' => 'ウード、アンバー'], 'variants' => [['sku' => 'GU-IOUD-50', 'size_ml' => 50, 'price_yen' => 14300, 'concentration' => 'EDP']]],
        ];
    }

    // === VERSACE (6 products) ===
    private function getVersaceProducts(): array
    {
        return [
            ['brand_name' => 'ヴェルサーチ', 'category_name' => 'フローラル EDT', 'name' => 'ブライトクリスタル オードトワレ', 'gender' => 'women', 'short_desc' => 'フレッシュでフルーティなフローラルの香り。', 'notes' => ['top' => 'ユズ、ポメグラネート', 'middle' => 'ピオニー、マグノリア、ロータス', 'base' => 'ムスク、マホガニー、アンバー'], 'featured' => true, 'variants' => [['sku' => 'VS-BC-30', 'size_ml' => 30, 'price_yen' => 6270, 'concentration' => 'EDT'], ['sku' => 'VS-BC-50', 'size_ml' => 50, 'price_yen' => 8800, 'concentration' => 'EDT'], ['sku' => 'VS-BC-90', 'size_ml' => 90, 'price_yen' => 12100, 'concentration' => 'EDT']]],
            ['brand_name' => 'ヴェルサーチ', 'category_name' => 'フレッシュ EDT', 'name' => 'エロス オードトワレ', 'gender' => 'men', 'short_desc' => '力強く情熱的な男性のための香り。', 'notes' => ['top' => 'ミント、レモン、グリーンアップル', 'middle' => 'トンカ、ジェノベーゼ', 'base' => 'バニラ、ベチバー、オークモス'], 'featured' => true, 'variants' => [['sku' => 'VS-EROS-50', 'size_ml' => 50, 'price_yen' => 7150, 'concentration' => 'EDT'], ['sku' => 'VS-EROS-100', 'size_ml' => 100, 'price_yen' => 9900, 'concentration' => 'EDT']]],
            ['brand_name' => 'ヴェルサーチ', 'category_name' => 'アロマティック EDT', 'name' => 'ディランブルー プールオム', 'gender' => 'men', 'short_desc' => '地中海の力強さと感性を表現した香り。', 'notes' => ['top' => 'シトラス、ベルガモット', 'middle' => 'ヴァイオレット、ペッパー', 'base' => 'インセンス、サンダルウッド'], 'variants' => [['sku' => 'VS-DB-50', 'size_ml' => 50, 'price_yen' => 7700, 'concentration' => 'EDT'], ['sku' => 'VS-DB-100', 'size_ml' => 100, 'price_yen' => 11000, 'concentration' => 'EDT']]],
            ['brand_name' => 'ヴェルサーチ', 'category_name' => 'フローラル EDT', 'name' => 'クリスタルノワール オードトワレ', 'gender' => 'women', 'short_desc' => '神秘的で官能的な香り。', 'notes' => ['top' => 'ブラックカラント、ビオラ', 'middle' => 'ダリア、フランジパニ', 'base' => 'バニラ、サンダルウッド、ムスク'], 'variants' => [['sku' => 'VS-CN-50', 'size_ml' => 50, 'price_yen' => 8800, 'concentration' => 'EDT']]],
            ['brand_name' => 'ヴェルサーチ', 'category_name' => 'フローラル EDT', 'name' => 'イエローダイヤモンド オードトワレ', 'gender' => 'women', 'short_desc' => '輝くような明るさとエレガンス。', 'notes' => ['top' => 'シトラス、ペア', 'middle' => 'ミモザ、フリージア', 'base' => 'ムスク、パロサント'], 'variants' => [['sku' => 'VS-YD-50', 'size_ml' => 50, 'price_yen' => 7700, 'concentration' => 'EDT']]],
            ['brand_name' => 'ヴェルサーチ', 'category_name' => 'フレッシュ EDT', 'name' => 'マン オーフレッシュ', 'gender' => 'men', 'short_desc' => '軽やかでフレッシュな男性の香り。', 'notes' => ['top' => 'レモン、ベルガモット', 'middle' => 'ローズ、カルダモン', 'base' => 'ムスク、シダー'], 'variants' => [['sku' => 'VS-MOF-50', 'size_ml' => 50, 'price_yen' => 7150, 'concentration' => 'EDT']]],
        ];
    }

    // === CHLOE (6 products) ===
    private function getChloeProducts(): array
    {
        return [
            ['brand_name' => 'クロエ', 'category_name' => 'フローラル EDP', 'name' => 'クロエ オードパルファム', 'gender' => 'women', 'short_desc' => '上品でフェミニンなローズの香り。', 'notes' => ['top' => 'ピオニー、フリージア', 'middle' => 'ローズ、リリーオブザバレー', 'base' => 'シダー、ムスク、アンバー'], 'featured' => true, 'variants' => [['sku' => 'CHLOE-EDP-30', 'size_ml' => 30, 'price_yen' => 8250, 'concentration' => 'EDP'], ['sku' => 'CHLOE-EDP-50', 'size_ml' => 50, 'price_yen' => 12100, 'concentration' => 'EDP'], ['sku' => 'CHLOE-EDP-75', 'size_ml' => 75, 'price_yen' => 15400, 'concentration' => 'EDP']]],
            ['brand_name' => 'クロエ', 'category_name' => 'ウッディ EDP', 'name' => 'ノマド オードパルファム', 'gender' => 'women', 'short_desc' => 'ミラベルプラムとオークモスの組み合わせ。', 'notes' => ['top' => 'ミラベルプラム', 'middle' => 'フリージア', 'base' => 'オークモス、サンダルウッド'], 'featured' => true, 'variants' => [['sku' => 'CHLOE-NOM-30', 'size_ml' => 30, 'price_yen' => 8250, 'concentration' => 'EDP'], ['sku' => 'CHLOE-NOM-50', 'size_ml' => 50, 'price_yen' => 12100, 'concentration' => 'EDP']]],
            ['brand_name' => 'クロエ', 'category_name' => 'フローラル EDP', 'name' => 'ローズタンジェリン オードトワレ', 'gender' => 'women', 'short_desc' => 'タンジェリンとローズの組み合わせ。', 'notes' => ['top' => 'タンジェリン、シトラス', 'middle' => 'ローズ', 'base' => 'シダー、ムスク'], 'variants' => [['sku' => 'CHLOE-RT-30', 'size_ml' => 30, 'price_yen' => 7150, 'concentration' => 'EDT']]],
            ['brand_name' => 'クロエ', 'category_name' => 'フローラル EDT', 'name' => 'クロエ ローズ オードトワレ', 'gender' => 'women', 'short_desc' => '軽やかなローズの香り。', 'notes' => ['top' => 'ローズ、シトラス', 'middle' => 'フローラル', 'base' => 'ムスク、シダー'], 'variants' => [['sku' => 'CHLOE-ROSE-50', 'size_ml' => 50, 'price_yen' => 9900, 'concentration' => 'EDT']]],
            ['brand_name' => 'クロエ', 'category_name' => 'フローラル EDP', 'name' => 'クロエ ナチュレル', 'gender' => 'women', 'short_desc' => 'ナチュラルでオーガニックな印象の香り。', 'notes' => ['top' => 'シトラス、フローラル', 'middle' => 'ローズ、ジャスミン', 'base' => 'シダー、ムスク'], 'variants' => [['sku' => 'CHLOE-NAT-50', 'size_ml' => 50, 'price_yen' => 12100, 'concentration' => 'EDP']]],
            ['brand_name' => 'クロエ', 'category_name' => 'フローラル EDP', 'name' => 'クロエ ルミヌーズ', 'gender' => 'women', 'short_desc' => '光を放つような明るいフローラル。', 'notes' => ['top' => 'フローラル、シトラス', 'middle' => 'ローズ、ピオニー', 'base' => 'ムスク、アンバー'], 'variants' => [['sku' => 'CHLOE-LUM-50', 'size_ml' => 50, 'price_yen' => 12100, 'concentration' => 'EDP']]],
        ];
    }

    // === YSL (6 products) ===
    private function getYslProducts(): array
    {
        return [
            ['brand_name' => 'イヴ・サンローラン', 'category_name' => 'オリエンタル EDP', 'name' => 'ブラックオピウム オードパルファム', 'gender' => 'women', 'short_desc' => 'コーヒーとバニラの中毒的な香り。', 'notes' => ['top' => 'コーヒー、ピンクペッパー', 'middle' => 'オレンジブロッサム、ジャスミン', 'base' => 'バニラ、パチュリ、シダー'], 'featured' => true, 'variants' => [['sku' => 'YSL-BO-30', 'size_ml' => 30, 'price_yen' => 8250, 'concentration' => 'EDP'], ['sku' => 'YSL-BO-50', 'size_ml' => 50, 'price_yen' => 12100, 'concentration' => 'EDP'], ['sku' => 'YSL-BO-90', 'size_ml' => 90, 'price_yen' => 16500, 'concentration' => 'EDP']]],
            ['brand_name' => 'イヴ・サンローラン', 'category_name' => 'フローラル EDP', 'name' => 'リブレ オードパルファム', 'gender' => 'women', 'short_desc' => 'ラベンダーとオレンジブロッサムのコントラスト。', 'notes' => ['top' => 'ラベンダー、マンダリン', 'middle' => 'オレンジブロッサム、ジャスミン', 'base' => 'バニラ、ムスク、シダー'], 'variants' => [['sku' => 'YSL-LIB-30', 'size_ml' => 30, 'price_yen' => 8250, 'concentration' => 'EDP'], ['sku' => 'YSL-LIB-50', 'size_ml' => 50, 'price_yen' => 12100, 'concentration' => 'EDP'], ['sku' => 'YSL-LIB-90', 'size_ml' => 90, 'price_yen' => 16500, 'concentration' => 'EDP']]],
            ['brand_name' => 'イヴ・サンローラン', 'category_name' => 'ウッディ EDT', 'name' => 'Y オードパルファム', 'gender' => 'men', 'short_desc' => 'リンゴとセージのフレッシュな香り。', 'notes' => ['top' => 'アップル、ジンジャー、ベルガモット', 'middle' => 'セージ、ジュニパー', 'base' => 'アンバーウッド、トンカ'], 'variants' => [['sku' => 'YSL-Y-60', 'size_ml' => 60, 'price_yen' => 9900, 'concentration' => 'EDP'], ['sku' => 'YSL-Y-100', 'size_ml' => 100, 'price_yen' => 14300, 'concentration' => 'EDP']]],
            ['brand_name' => 'イヴ・サンローラン', 'category_name' => 'フローラル EDP', 'name' => 'モンパリ オードパルファム', 'gender' => 'women', 'short_desc' => 'パリの街をイメージした甘くロマンティックな香り。', 'notes' => ['top' => 'ストロベリー、ラズベリー、ペア', 'middle' => 'ダチュラ、ジャスミン', 'base' => 'パチュリ、バニラ'], 'variants' => [['sku' => 'YSL-MP-30', 'size_ml' => 30, 'price_yen' => 8250, 'concentration' => 'EDP'], ['sku' => 'YSL-MP-50', 'size_ml' => 50, 'price_yen' => 12100, 'concentration' => 'EDP']]],
            ['brand_name' => 'イヴ・サンローラン', 'category_name' => 'アロマティック EDT', 'name' => 'ラニュイドゥロム オードトワレ', 'gender' => 'men', 'short_desc' => 'カルダモンとシダーの魅惑的な香り。', 'notes' => ['top' => 'カルダモン、ベルガモット', 'middle' => 'ラベンダー、ベチバー', 'base' => 'バニラ、シダー'], 'variants' => [['sku' => 'YSL-LN-60', 'size_ml' => 60, 'price_yen' => 8800, 'concentration' => 'EDT'], ['sku' => 'YSL-LN-100', 'size_ml' => 100, 'price_yen' => 12650, 'concentration' => 'EDT']]],
            ['brand_name' => 'イヴ・サンローラン', 'category_name' => 'オリエンタル EDP', 'name' => 'オピウム オードパルファム', 'gender' => 'women', 'short_desc' => '伝説的なクラシックフレグランス。', 'notes' => ['top' => 'コリアンダー、プラム', 'middle' => 'ジャスミン、ローズ', 'base' => 'アンバー、パチュリ'], 'variants' => [['sku' => 'YSL-OPI-30', 'size_ml' => 30, 'price_yen' => 9350, 'concentration' => 'EDP']]],
        ];
    }

    // === PRADA (6 products) ===
    private function getPradaProducts(): array
    {
        return [
            ['brand_name' => 'プラダ', 'category_name' => 'フローラル EDP', 'name' => 'プラダ パラドックス オードパルファム', 'gender' => 'women', 'short_desc' => '矛盾を楽しむ現代の女性のための香り。', 'notes' => ['top' => 'ペア、タンジェリン', 'middle' => 'オレンジフラワー、ジャスミン', 'base' => 'バニラ、ベチバー、ホワイトムスク'], 'featured' => true, 'variants' => [['sku' => 'PR-PAR-30', 'size_ml' => 30, 'price_yen' => 9900, 'concentration' => 'EDP'], ['sku' => 'PR-PAR-50', 'size_ml' => 50, 'price_yen' => 14300, 'concentration' => 'EDP'], ['sku' => 'PR-PAR-90', 'size_ml' => 90, 'price_yen' => 19800, 'concentration' => 'EDP']]],
            ['brand_name' => 'プラダ', 'category_name' => 'アロマティック EDT', 'name' => 'ルナロッサ オードトワレ', 'gender' => 'men', 'short_desc' => 'ラベンダーとオレンジのフレッシュな組み合わせ。', 'notes' => ['top' => 'ラベンダー、オレンジ', 'middle' => 'ミント、クレモナ', 'base' => 'アンバー、バニラ'], 'variants' => [['sku' => 'PR-LR-50', 'size_ml' => 50, 'price_yen' => 9350, 'concentration' => 'EDT'], ['sku' => 'PR-LR-100', 'size_ml' => 100, 'price_yen' => 13200, 'concentration' => 'EDT']]],
            ['brand_name' => 'プラダ', 'category_name' => 'アロマティック EDT', 'name' => 'ルナロッサ オーシャン オードトワレ', 'gender' => 'men', 'short_desc' => '海のエネルギーを感じさせるアクアティックフレグランス。', 'notes' => ['top' => 'アクアティックノート、ベルガモット', 'middle' => 'アイリス、ラベンダー', 'base' => 'パチュリ、オークモス'], 'variants' => [['sku' => 'PR-LRO-50', 'size_ml' => 50, 'price_yen' => 9900, 'concentration' => 'EDT']]],
            ['brand_name' => 'プラダ', 'category_name' => 'フローラル EDP', 'name' => 'キャンディ オードパルファム', 'gender' => 'women', 'short_desc' => 'キャラメルの甘く愛らしい香り。', 'notes' => ['top' => 'シトラス', 'middle' => 'ピオニー', 'base' => 'キャラメル、バニラ、ベンゾイン'], 'variants' => [['sku' => 'PR-CAN-30', 'size_ml' => 30, 'price_yen' => 8250, 'concentration' => 'EDP'], ['sku' => 'PR-CAN-50', 'size_ml' => 50, 'price_yen' => 12100, 'concentration' => 'EDP']]],
            ['brand_name' => 'プラダ', 'category_name' => 'フローラル EDP', 'name' => 'プラダ パラドックス インテンス', 'gender' => 'women', 'short_desc' => 'より深みのあるパラドックス。', 'notes' => ['top' => 'ペア', 'middle' => 'ジャスミン、ローズ', 'base' => 'バニラ、アンバー'], 'variants' => [['sku' => 'PR-PARI-50', 'size_ml' => 50, 'price_yen' => 15400, 'concentration' => 'EDP']]],
            ['brand_name' => 'プラダ', 'category_name' => 'ウッディ EDP', 'name' => 'ローミュ オードパルファム', 'gender' => 'men', 'short_desc' => '高級皮革のような洗練された香り。', 'notes' => ['top' => 'ベルガモット、ピンクペッパー', 'middle' => 'ラベンダー、ゼラニウム', 'base' => 'レザー、アンバー'], 'variants' => [['sku' => 'PR-LHOM-50', 'size_ml' => 50, 'price_yen' => 11000, 'concentration' => 'EDP']]],
        ];
    }

    // === ARMANI (6 products) ===
    private function getArmaniProducts(): array
    {
        return [
            ['brand_name' => 'ジョルジオ・アルマーニ', 'category_name' => 'アロマティック EDT', 'name' => 'アクアディジオ オードトワレ', 'gender' => 'men', 'short_desc' => '海と太陽を感じさせるクラシックなフレグランス。', 'notes' => ['top' => 'ライム、レモン、ベルガモット', 'middle' => 'ジャスミン、スズラン、ローズマリー', 'base' => 'パチュリ、ホワイトムスク、シダー'], 'featured' => true, 'variants' => [['sku' => 'GA-ADG-50', 'size_ml' => 50, 'price_yen' => 9350, 'concentration' => 'EDT'], ['sku' => 'GA-ADG-100', 'size_ml' => 100, 'price_yen' => 13200, 'concentration' => 'EDT'], ['sku' => 'GA-ADG-200', 'size_ml' => 200, 'price_yen' => 18700, 'concentration' => 'EDT']]],
            ['brand_name' => 'ジョルジオ・アルマーニ', 'category_name' => 'ウッディ EDP', 'name' => 'アクアディジオ オードパルファム', 'gender' => 'men', 'short_desc' => 'より深みのあるアクアディジオ。', 'notes' => ['top' => 'マリンノート、ベルガモット', 'middle' => 'ローズマリー、セージ', 'base' => 'インセンス、パチュリ'], 'variants' => [['sku' => 'GA-ADG-EDP-50', 'size_ml' => 50, 'price_yen' => 11000, 'concentration' => 'EDP'], ['sku' => 'GA-ADG-EDP-100', 'size_ml' => 100, 'price_yen' => 15400, 'concentration' => 'EDP']]],
            ['brand_name' => 'ジョルジオ・アルマーニ', 'category_name' => 'フローラル EDP', 'name' => 'シー オードパルファム', 'gender' => 'women', 'short_desc' => 'ブラックカラントとバニラの組み合わせ。', 'notes' => ['top' => 'ブラックカラント、シトラス', 'middle' => 'ローズ、ジャスミン', 'base' => 'バニラ、パチュリ、ウッディ'], 'variants' => [['sku' => 'GA-SI-30', 'size_ml' => 30, 'price_yen' => 8800, 'concentration' => 'EDP'], ['sku' => 'GA-SI-50', 'size_ml' => 50, 'price_yen' => 13200, 'concentration' => 'EDP'], ['sku' => 'GA-SI-100', 'size_ml' => 100, 'price_yen' => 18700, 'concentration' => 'EDP']]],
            ['brand_name' => 'ジョルジオ・アルマーニ', 'category_name' => 'オリエンタル EDP', 'name' => 'コード プールファム', 'gender' => 'women', 'short_desc' => 'オレンジブロッサムとバニラの魅惑的な香り。', 'notes' => ['top' => 'イタリアンマンダリン、オレンジ', 'middle' => 'オレンジブロッサム、ジャスミン', 'base' => 'バニラ、ハニー'], 'variants' => [['sku' => 'GA-CODE-30', 'size_ml' => 30, 'price_yen' => 9350, 'concentration' => 'EDP'], ['sku' => 'GA-CODE-50', 'size_ml' => 50, 'price_yen' => 13750, 'concentration' => 'EDP']]],
            ['brand_name' => 'ジョルジオ・アルマーニ', 'category_name' => 'フレッシュ EDT', 'name' => 'コード プールオム オードトワレ', 'gender' => 'men', 'short_desc' => 'レモンとオリーブのフレッシュな香り。', 'notes' => ['top' => 'レモン、ベルガモット', 'middle' => 'オリーブフラワー、スターアニス', 'base' => 'レザー、ギアウッド、タバコ'], 'variants' => [['sku' => 'GA-CODEM-50', 'size_ml' => 50, 'price_yen' => 9350, 'concentration' => 'EDT'], ['sku' => 'GA-CODEM-100', 'size_ml' => 100, 'price_yen' => 13200, 'concentration' => 'EDT']]],
            ['brand_name' => 'ジョルジオ・アルマーニ', 'category_name' => 'ウッディ EDT', 'name' => 'ストロンガーウィズユー オードトワレ', 'gender' => 'men', 'short_desc' => 'バニラと栗の温かみのある香り。', 'notes' => ['top' => 'バイオレット、チェストナッツ', 'middle' => 'セージ', 'base' => 'バニラ、ムスク'], 'variants' => [['sku' => 'GA-SWU-50', 'size_ml' => 50, 'price_yen' => 9350, 'concentration' => 'EDT']]],
        ];
    }

    // === JO MALONE (6 products) ===
    private function getJoMaloneProducts(): array
    {
        return [
            ['brand_name' => 'ジョーマローン', 'category_name' => 'シトラス EDT', 'name' => 'ライムバジル＆マンダリン コロン', 'gender' => 'unisex', 'short_desc' => 'カリブ海の風を感じさせる爽やかなシトラス。', 'notes' => ['top' => 'ライム、マンダリン', 'middle' => 'バジル、タイム', 'base' => 'リリー、ベチバー'], 'featured' => true, 'variants' => [['sku' => 'JM-LBM-30', 'size_ml' => 30, 'price_yen' => 8800, 'concentration' => 'EDC'], ['sku' => 'JM-LBM-100', 'size_ml' => 100, 'price_yen' => 17600, 'concentration' => 'EDC']]],
            ['brand_name' => 'ジョーマローン', 'category_name' => 'フルーティー EDT', 'name' => 'イングリッシュペアー＆フリージア コロン', 'gender' => 'unisex', 'short_desc' => '熟したペアーと白いフリージアの組み合わせ。', 'notes' => ['top' => 'キングウィリアムペアー', 'middle' => 'フリージア', 'base' => 'パチュリ'], 'featured' => true, 'variants' => [['sku' => 'JM-EPF-30', 'size_ml' => 30, 'price_yen' => 8800, 'concentration' => 'EDC'], ['sku' => 'JM-EPF-100', 'size_ml' => 100, 'price_yen' => 17600, 'concentration' => 'EDC']]],
            ['brand_name' => 'ジョーマローン', 'category_name' => 'ウッディ EDT', 'name' => 'ウッドセージ＆シーソルト コロン', 'gender' => 'unisex', 'short_desc' => '海風とミネラルを感じさせるフレッシュなウッディ。', 'notes' => ['top' => 'シーソルト', 'middle' => 'セージ', 'base' => 'グラープフルーツ、ムスク'], 'variants' => [['sku' => 'JM-WSS-30', 'size_ml' => 30, 'price_yen' => 8800, 'concentration' => 'EDC'], ['sku' => 'JM-WSS-100', 'size_ml' => 100, 'price_yen' => 17600, 'concentration' => 'EDC']]],
            ['brand_name' => 'ジョーマローン', 'category_name' => 'フローラル EDT', 'name' => 'ワイルドブルーベル コロン', 'gender' => 'women', 'short_desc' => 'ブルーベルの花が咲き誇る森の香り。', 'notes' => ['top' => 'ブルーベル', 'middle' => 'クローブ、リリー', 'base' => 'ムスク'], 'variants' => [['sku' => 'JM-WB-30', 'size_ml' => 30, 'price_yen' => 8800, 'concentration' => 'EDC'], ['sku' => 'JM-WB-100', 'size_ml' => 100, 'price_yen' => 17600, 'concentration' => 'EDC']]],
            ['brand_name' => 'ジョーマローン', 'category_name' => 'フローラル EDT', 'name' => 'ピオニー＆ブラッシュスエード コロン', 'gender' => 'women', 'short_desc' => '華やかなピオニーとスエードの組み合わせ。', 'notes' => ['top' => 'レッドアップル', 'middle' => 'ピオニー', 'base' => 'スエード'], 'variants' => [['sku' => 'JM-PBS-30', 'size_ml' => 30, 'price_yen' => 8800, 'concentration' => 'EDC'], ['sku' => 'JM-PBS-100', 'size_ml' => 100, 'price_yen' => 17600, 'concentration' => 'EDC']]],
            ['brand_name' => 'ジョーマローン', 'category_name' => 'フルーティー EDT', 'name' => 'ネクタリンブロッサム＆ハニー コロン', 'gender' => 'women', 'short_desc' => 'ネクタリンの甘さと蜂蜜の温かみ。', 'notes' => ['top' => 'ネクタリン', 'middle' => 'アカシアハニー', 'base' => 'ピーチ'], 'variants' => [['sku' => 'JM-NBH-30', 'size_ml' => 30, 'price_yen' => 8800, 'concentration' => 'EDC']]],
        ];
    }
}
