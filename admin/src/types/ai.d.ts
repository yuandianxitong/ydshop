import type { PageQuery } from './common'

// ========== AI 任务 ==========
export interface AiTaskInfo {
    id: number
    type: string
    type_text?: string
    driver?: string
    model?: string
    status: string
    status_text?: string
    prompt?: string
    response?: string
    output?: string
    result?: any
    error_message?: string
    duration?: number
    duration_ms?: number
    tokens?: number
    tokens_used?: number
    cost?: number
    operator_id?: number
    operator_name?: string
    created_at: string
    finished_at?: string
}

export interface AiTaskQuery extends PageQuery {
    type?: string
    status?: string
    driver?: string
    start_date?: string
    end_date?: string
}

// ========== AI 提示词模板 ==========
export interface AiPromptInfo {
    id: number
    type: string
    name: string
    system_prompt: string
    user_prompt_template: string
    variables: Record<string, string> | null
    is_default: number
    status: number
    created_at: string
    updated_at?: string
}

export interface AiPromptReq {
    id?: number
    type: string
    name: string
    system_prompt: string
    user_prompt_template: string
    variables?: Record<string, string>
    is_default?: number
    status?: number
}

export interface AiPromptQuery extends PageQuery {
    type?: string
    status?: number
}

// ========== AI 配置 ==========
export interface AiDriverConfig {
    api_key?: string
    api_base?: string
    model?: string
    [key: string]: any
}

export interface AiConfigInfo {
    enabled: number
    default_driver: string
    drivers: Record<string, AiDriverConfig>
    scenes?: Record<string, string>
    [key: string]: any
}

export interface AiDriverOption {
    name: string
    label: string
    models?: string[]
}

// ========== AI 销售分析参数 ==========
export interface AiAnalysisReq {
    days?: number
    metric?: string
    prompt_id?: number
    [key: string]: any
}
