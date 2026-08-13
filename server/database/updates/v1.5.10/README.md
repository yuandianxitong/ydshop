# v1.5.10 升级说明

## 变更
- 新人礼包 Listener 在 register 事件触发时按每张礼包的 `conditions[]` AND 语义评估，仅对匹配的礼包发放（修复 SP-B1/B2 的"全发"行为）
- 前端 new-user-gift 页面新增"分发策略" tab，静态展示发放逻辑与受众标签判断规则

## 升级方式
本版本无 SQL 变更，纯应用层升版。直接更新代码并部署即可。

## 行为变化（重要）
- 配置了 `'profile_complete'` 受众标签的礼包：之前所有新用户都会收到（bug），现在只有 nickname 和 avatar 都填写的用户才会收到
- 配置了 `'invited'` 受众标签的礼包：仅 inviter_id > 0 的用户才会收到
- 仅含 `'new_register'` / `'no_order_7d'` 标签的礼包行为不变（这两个 condition 在 register 时刻恒为真）
- 已存在用户（claimed_at 已写）不变，行为变化仅影响新注册用户

## 受众标签判断口径

| 标签 | 判断 |
|---|---|
| `new_register` | 恒为真 |
| `no_order_7d` | register 时恒为真（0 单），未来事件触发后才有差别 |
| `invited` | `users.inviter_id > 0` |
| `profile_complete` | `nickname != '' AND avatar != ''` |
