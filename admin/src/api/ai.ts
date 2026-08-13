import type {
    AiAnalysisReq,
    AiConfigInfo,
    AiDriverOption,
    AiPromptInfo,
    AiPromptQuery,
    AiPromptReq,
    AiTaskInfo,
    AiTaskQuery,
    PageResult
} from '@/types/api'
import { myRequest } from '@/utils/request'

// AI 助手 API

export const AI_TEXT_STREAM_URL = '/adminapi/ai/text/stream'

export type AiTextScene = 'title' | 'description' | 'detail'
export type AiImageScene = 'cover' | 'gallery'

export interface AiTextReq {
    scene: AiTextScene
    spu_id?: number
    context?: {
        name?: string
        category?: string
        brand?: string
        specs?: string
        attributes?: string
        current_text?: string
    }
    style?: string
    driver?: string
}

export interface AiImageReq {
    scene: AiImageScene
    spu_id?: number
    context?: { name?: string; category?: string; brand?: string }
    prompt_extra?: string
    size?: string
    n?: number
}

export interface AiImageResp {
    task_id: number
    urls: string[]
    cost: number
    duration_ms: number
}

export const aiConfigApi = {
    getAiConfig() { return myRequest.get<AiConfigInfo>('/adminapi/ai/config') },
    updateAiConfig(data: Partial<AiConfigInfo>) { return myRequest.put<void>('/adminapi/ai/config', data) },
    getAiDrivers() { return myRequest.get<AiDriverOption[]>('/adminapi/ai/drivers') },
}

export const aiTaskApi = {
    getTaskList(params: AiTaskQuery) { return myRequest.get<PageResult<AiTaskInfo>>('/adminapi/ai/tasks', { params }) },
    getTaskDetail(id: number) { return myRequest.get<AiTaskInfo>(`/adminapi/ai/tasks/${id}`) },
}

export const aiPromptApi = {
    getPromptList(params: AiPromptQuery) { return myRequest.get<PageResult<AiPromptInfo>>('/adminapi/ai/prompts', { params }) },
    createPrompt(data: AiPromptReq) { return myRequest.post<void>('/adminapi/ai/prompts', data) },
    updatePrompt(id: number, data: Partial<AiPromptReq>) { return myRequest.put<void>(`/adminapi/ai/prompts/${id}`, data) },
    deletePrompt(id: number) { return myRequest.delete<void>(`/adminapi/ai/prompts/${id}`) },
}

export const aiAnalysisApi = {
    runAnalysis(params: AiAnalysisReq) { return myRequest.post<AiTaskInfo>('/adminapi/ai/analysis', params) },
}

export const aiTextApi = {
    runText(data: AiTextReq) { return myRequest.post<AiTaskInfo & { output: string }>('/adminapi/ai/text', data) },
    STREAM_URL: AI_TEXT_STREAM_URL,
}

export const aiImageApi = {
    runImage(data: AiImageReq) {
        return myRequest.post<AiImageResp>('/adminapi/ai/image', data, { timeout: 120_000 } as any)
    },
}
