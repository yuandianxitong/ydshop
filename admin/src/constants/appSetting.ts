// src/constants/appSetting.ts

export const appConfig = {
    terminal: 1, // 终端标识
    title: '元点Shop', // 网站默认标题（运行时会被 i18n 覆盖）
    language: 'zh-cn', // 默认语言
    version: '1.0.0', // 版本号
    baseUrl: import.meta.env.VITE_APP_API_URL || '', // 后端 API 地址（留空 = 当前域名，跨域部署时填写后端域名）
    urlPrefix: 'adminapi', // 接口默认前缀
    timeout: 10 * 1000 // 请求超时时长（单位：毫秒）
}
