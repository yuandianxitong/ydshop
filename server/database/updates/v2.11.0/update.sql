-- v2.11.0: PC 首页 DIY 预设（Banner + 5 列魔方）
-- 幂等：只有当 PC home 页 components 为空（或不存在）时才覆盖，绝不破坏老用户自定义内容
--
-- 改动：
--   diy_pages id=2 (page_type=home, platform=pc) 的 components 由空数组替换为
--   banner(1 张)+ image-cube(5 列 × 1 行) + 原有 category-nav/goods-grid 组合
--
-- 配套：6 张图片随 git 提交在 server/public/storage/diy-defaults/home/pc/{banner,cube}/
--      Web 服务器直接 serve，无需安装时复制

UPDATE `diy_pages`
SET `components` = '[{"id":"comp_1","type":"banner","props":{"items":[{"image":"/storage/diy-defaults/home/pc/banner/01.png","url":"","title":""}],"autoplay":true,"interval":3000,"height":460}},{"id":"comp_2","type":"image-cube","props":{"rows":1,"cols":5,"gap":8,"borderRadius":4,"marginTop":16,"marginBottom":16,"items":[{"image":"/storage/diy-defaults/home/pc/cube/01.png","link":"","rowStart":1,"colStart":1,"rowSpan":1,"colSpan":1},{"image":"/storage/diy-defaults/home/pc/cube/02.png","link":"","rowStart":1,"colStart":2,"rowSpan":1,"colSpan":1},{"image":"/storage/diy-defaults/home/pc/cube/03.png","link":"","rowStart":1,"colStart":3,"rowSpan":1,"colSpan":1},{"image":"/storage/diy-defaults/home/pc/cube/04.png","link":"","rowStart":1,"colStart":4,"rowSpan":1,"colSpan":1},{"image":"/storage/diy-defaults/home/pc/cube/05.png","link":"","rowStart":1,"colStart":5,"rowSpan":1,"colSpan":1}]}},{"id":"comp_3","type":"title-bar","props":{"title":"热销推荐","subtitle":"精选好物","align":"center","more_url":"/goods","more_text":"查看更多"}},{"id":"comp_4","type":"goods-grid","props":{"title":"","source":"tag","goods_ids":[],"category_id":null,"tag":"hot","limit":8,"columns":4}},{"id":"comp_5","type":"title-bar","props":{"title":"新品上架","subtitle":"","align":"center","more_url":"/goods?sort=new","more_text":"查看更多"}},{"id":"comp_6","type":"goods-grid","props":{"title":"","source":"tag","goods_ids":[],"category_id":null,"tag":"new","limit":8,"columns":4}}]',
    `updated_at` = NOW()
WHERE `page_type` = 'home'
  AND `platform`  = 'pc'
  AND `deleted_at` IS NULL
  AND (
    -- Case A: 完全空（NULL 或 [])
    `components` IS NULL
    OR JSON_LENGTH(`components`) = 0
    -- Case B: v2.10.x init.sql 写入的占位结构 —— 用结构性指纹识别，避免对 JSON 字面量做相等比较
    -- 指纹：恰好 6 个组件 + 第 1 个是 banner 且 items 为空 + 第 2 个是 category-nav。
    -- 任何自定义过的用户都不会同时满足这三条（即便组件数恰好 6 个，items 也不会为空）。
    -- 兼容 MySQL 8.0 / MariaDB 10.5+
    OR (
      JSON_LENGTH(`components`) = 6
      AND JSON_UNQUOTE(JSON_EXTRACT(`components`, '$[0].type')) = 'banner'
      AND JSON_LENGTH(JSON_EXTRACT(`components`, '$[0].props.items')) = 0
      AND JSON_UNQUOTE(JSON_EXTRACT(`components`, '$[1].type')) = 'category-nav'
    )
  );
