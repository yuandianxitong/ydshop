// ========== 代码生成器 ==========
export interface GeneratorTableInfo {
    name: string
    comment: string
    engine: string
    rows: number
}

export interface GeneratorColumnInfo {
    name: string
    type: string
    raw_type: string
    nullable: boolean
    default: string | null
    comment: string
    key: string
    extra: string
    form_type: string
    searchable: boolean
    in_list: boolean
    in_form: boolean
}

export interface GeneratorConfig {
    table_name: string
    module_name: string
    model_name: string
    table_comment?: string
    columns?: GeneratorColumnInfo[]
    author?: string
    template_type?: string
    perm_prefix?: string
    api_prefix?: string
    backend_path?: string
    frontend_path?: string
    parent_menu?: string | number
    gen_method?: string
    templates?: string[]
}

export interface GeneratorPreviewFile {
    path: string
    content: string
}

export interface GeneratorPreviewResult {
    [filename: string]: GeneratorPreviewFile
}
