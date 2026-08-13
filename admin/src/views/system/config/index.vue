<template>
    <div class="system-config">
        <div class="page-head">
            <div>
                <div class="page-title">系统设置</div>
                <div class="page-desc">配置全局基础信息、安全策略、通知与存储参数</div>
            </div>
            <div class="page-actions">
                <el-button @click="handleReset">取消</el-button>
                <el-button type="primary" @click="handleSave" :loading="loading">保存全部</el-button>
            </div>
        </div>

        <div class="set-split">
            <!-- 左侧导航 -->
            <div class="set-nav">
                <div
                    v-for="(label, key) in configGroups"
                    :key="key"
                    class="set-nav-item"
                    :class="{ on: activeTab === key }"
                    @click="switchTab(key)"
                >
                    <i class="ic" :class="navIconClass[key] || 'i-svg:settings'" />
                    <span>{{ label }}</span>
                </div>
            </div>

            <!-- 右侧内容 -->
            <div class="set-sections">
                <div class="set-card">
                    <div class="set-card-head">
                        <h3>{{ configGroups[activeTab] || activeTab }}</h3>
                    </div>
                    <div class="set-card-body">
                        <template v-for="config in visibleConfigs" :key="config.id">
                            <div class="set-item">
                                <div>
                                    <div class="set-item-label">{{ getItemName(config) }}</div>
                                    <div
                                        v-if="config.config_desc && config.config_type !== 'file'"
                                        class="set-item-desc"
                                    >
                                        {{ getItemDesc(config) }}
                                    </div>
                                </div>
                                <div class="set-item-ctrl">
                                    <!-- select 下拉选择 -->
                                    <template v-if="config.config_type === 'select'">
                                        <el-select
                                            v-model="formData[config.config_key]"
                                            :placeholder="$t('config.selectPlaceholder')"
                                            style="width: 320px"
                                        >
                                            <el-option
                                                v-for="(optLabel, optValue) in parseOptions(
                                                    config.config_options
                                                )"
                                                :key="optValue"
                                                :label="
                                                    getOptionLabel(
                                                        config.config_key,
                                                        String(optValue),
                                                        String(optLabel)
                                                    )
                                                "
                                                :value="optValue"
                                            />
                                        </el-select>
                                    </template>

                                    <!-- boolean 开关 -->
                                    <template v-else-if="config.config_type === 'boolean'">
                                        <el-switch
                                            v-model="formData[config.config_key]"
                                            :active-value="1"
                                            :inactive-value="0"
                                        />
                                    </template>

                                    <!-- number 数字 -->
                                    <template v-else-if="config.config_type === 'number'">
                                        <el-input-number
                                            v-model="formData[config.config_key]"
                                            :min="0"
                                            style="width: 200px"
                                        />
                                    </template>

                                    <!-- file 文件上传 -->
                                    <template v-else-if="config.config_type === 'file'">
                                        <div>
                                            <el-upload
                                                class="image-uploader"
                                                :show-file-list="false"
                                                :on-success="
                                                    (response: any) =>
                                                        handleUploadSuccess(
                                                            response,
                                                            config.config_key
                                                        )
                                                "
                                                :before-upload="beforeUpload"
                                                action="/adminapi/upload/image"
                                                :headers="uploadHeaders"
                                            >
                                                <img
                                                    v-if="formData[config.config_key]"
                                                    :src="getImageUrl(formData[config.config_key])"
                                                    class="uploaded-image"
                                                    :alt="getItemName(config)"
                                                />
                                                <i v-else class="i-svg:plus image-uploader-icon" />
                                            </el-upload>
                                            <div class="upload-tip">
                                                {{ getItemDesc(config) }}，{{
                                                    $t('config.fileTip')
                                                }}
                                            </div>
                                        </div>
                                    </template>

                                    <!-- string 默认文本 -->
                                    <template v-else>
                                        <el-input
                                            v-model="formData[config.config_key]"
                                            :type="
                                                config.config_key.includes('password') ||
                                                config.config_key.includes('secret') ||
                                                config.config_key.includes('private_key')
                                                    ? 'password'
                                                    : 'text'
                                            "
                                            :show-password="
                                                config.config_key.includes('password') ||
                                                config.config_key.includes('secret') ||
                                                config.config_key.includes('private_key')
                                            "
                                            :placeholder="getItemDesc(config)"
                                            style="width: 400px"
                                        />
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts" name="SystemConfig">
import type { FormInstance, FormRules } from 'element-plus'
import { ElMessage, ElMessageBox } from 'element-plus'
import { computed, onMounted, reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'

import { batchUpdateConfigs, getConfigGroups, getConfigsByGroup } from '@/api/system/config'
import { useAppStore } from '@/store'
import { getToken } from '@/utils/auth'

const { t, te } = useI18n()

// 左侧导航图标映射（与分组标题语义对应）
const navIconClass: Record<string, string> = {
    basic: 'i-svg:sliders-horizontal',
    look: 'i-svg:palette',
    security: 'i-svg:lock',
    notify: 'i-svg:bell',
    storage: 'i-svg:server',
    email: 'i-svg:mail',
    sms: 'i-svg:smartphone',
    payment: 'i-svg:wallet',
    amap: 'i-svg:map-pin',
    about: 'i-svg:info',
}

// 获取配置项翻译名称（优先 i18n，fallback 到数据库值）
const getItemName = (config: any): string => {
    const key = `config.items.${config.config_key}`
    return te(key) ? t(key) : config.config_name
}

// 获取配置项翻译描述
const getItemDesc = (config: any): string => {
    const key = `config.descs.${config.config_key}`
    return te(key) ? t(key) : config.config_desc
}

// 获取下拉选项翻译
const getOptionLabel = (configKey: string, optValue: string, fallback: string): string => {
    const key = `config.options.${configKey}.${optValue}`
    return te(key) ? t(key) : fallback
}

const activeTab = ref('basic')
const loading = ref(false)
const formRef = ref<FormInstance>()
const appStore = useAppStore()

const configGroups = ref<Record<string, string>>({})
const configsData = reactive<Record<string, any[]>>({})
const formData = reactive<Record<string, any>>({})
const originalFormData = reactive<Record<string, any>>({})

// 上传相关配置（computed 确保 Token 刷新后仍有效）
const uploadHeaders = computed(() => ({
    Authorization: `Bearer ${getToken()}`
}))

const rules: FormRules = {
    site_name: [
        { required: true, message: t('config.validate.siteNameRequired'), trigger: 'blur' }
    ],
    site_url: [
        { required: true, message: t('config.validate.siteUrlRequired'), trigger: 'blur' },
        { type: 'url', message: t('config.validate.siteUrlFormat'), trigger: 'blur' }
    ],
    site_phone: [
        { pattern: /^1[3-9]\d{9}$/, message: t('config.validate.mobileFormat'), trigger: 'blur' }
    ]
}

const currentConfigs = computed(() => {
    return configsData[activeTab.value] || []
})

/**
 * 解析 config_options JSON 为对象
 */
const parseOptions = (options: any): Record<string, string> => {
    if (!options) return {}
    if (typeof options === 'string') {
        try {
            return JSON.parse(options)
        } catch {
            return {}
        }
    }
    return options
}

/**
 * 判断配置项是否满足 depends 条件
 */
const checkDepends = (config: any): boolean => {
    if (!config.config_depends) return true

    let depends = config.config_depends
    if (typeof depends === 'string') {
        try {
            depends = JSON.parse(depends)
        } catch {
            return true
        }
    }

    const field = depends.field
    const expectedValue = depends.value
    const currentValue = String(formData[field] ?? '')

    if (Array.isArray(expectedValue)) {
        return expectedValue.map(String).includes(currentValue)
    }
    return currentValue === String(expectedValue)
}

/**
 * 过滤后的可见配置列表（根据 depends 条件）
 */
const visibleConfigs = computed(() => {
    return currentConfigs.value.filter(checkDepends)
})

// 获取图片完整URL
const getImageUrl = (url: string) => {
    return appStore.getImageUrl(url)
}

onMounted(async () => {
    await loadConfigGroups()
    await loadConfigs(activeTab.value)
})

const loadConfigGroups = async () => {
    try {
        const res = await getConfigGroups()
        configGroups.value = res.data || {}
    } catch (error) {
        console.error(t('message.fetchFailed'), error)
    }
}

const loadConfigs = async (group: string) => {
    try {
        loading.value = true
        const res = await getConfigsByGroup(group)
        const configs = res.data || []

        configsData[group] = configs

        // 重置表单数据
        const newFormData: Record<string, any> = {}
        const newOriginalFormData: Record<string, any> = {}

        configs.forEach((config: any) => {
            let value = config.config_value

            // 根据类型转换值
            if (config.config_type === 'boolean') {
                value = Number(value)
            } else if (config.config_type === 'number') {
                value = Number(value)
            } else if (config.config_type === 'json') {
                try {
                    value = JSON.parse(value)
                } catch {
                    value = {}
                }
            }

            newFormData[config.config_key] = value
            newOriginalFormData[config.config_key] = value
        })

        Object.assign(formData, newFormData)
        Object.assign(originalFormData, newOriginalFormData)
    } catch (error) {
        console.error(t('message.fetchFailed'), error)
        ElMessage.error(t('message.fetchFailed'))
    } finally {
        loading.value = false
    }
}

const switchTab = async (key: string) => {
    activeTab.value = key
    if (!configsData[key]) {
        await loadConfigs(key)
    }
}

const handleTabChange = async (tabName: string | number) => {
    const group = String(tabName)
    if (!configsData[group]) {
        await loadConfigs(group)
    }
}

const handleUploadSuccess = (response: Record<string, any>, configKey: string) => {
    if (response.code === 200 || response.code === 0) {
        // 存储相对路径到数据库，用于后续配置保存
        formData[configKey] = response.data?.url || response.data?.path || response.data
        ElMessage.success(t('message.uploadSuccess'))
    } else {
        ElMessage.error(response.message || t('message.uploadFailed'))
    }
}

const beforeUpload = (file: File) => {
    // 检查文件类型
    const isImage = file.type.startsWith('image/')
    if (!isImage) {
        ElMessage.error(t('message.imageOnly'))
        return false
    }

    // 检查文件大小
    const isValidSize = file.size / 1024 / 1024 < 2
    if (!isValidSize) {
        ElMessage.error(t('message.fileSizeLimit'))
        return false
    }

    return true
}

const handleSave = async () => {
    try {
        loading.value = true

        // 准备更新数据 - 包含所有当前分组的配置（含隐藏的）
        const updateConfigs = currentConfigs.value.map((config) => {
            let value = formData[config.config_key]

            // 根据类型处理值
            if (config.config_type === 'json') {
                value = JSON.stringify(value)
            } else {
                value = String(value)
            }

            return {
                config_key: config.config_key,
                config_value: value
            }
        })

        await batchUpdateConfigs(updateConfigs)

        // 更新原始数据
        Object.assign(originalFormData, formData)

        ElMessage.success(t('message.configSaveSuccess'))
    } catch (error) {
        console.error(t('message.configSaveFailed'), error)
        if (error instanceof Error) {
            ElMessage.error(t('message.configSaveFailed') + ': ' + error.message)
        } else {
            ElMessage.error(t('message.configSaveFailed'))
        }
    } finally {
        loading.value = false
    }
}

const handleReset = () => {
    ElMessageBox.confirm(t('message.configResetConfirm'), t('common.tip'), {
        confirmButtonText: t('common.confirm'),
        cancelButtonText: t('common.cancel'),
        type: 'warning'
    })
        .then(() => {
            Object.assign(formData, originalFormData)
            ElMessage.success(t('message.configResetDone'))
        })
        .catch(() => {
            // 用户取消，静默吞掉，避免触发 ErrorBoundary
        })
}
</script>

<style scoped lang="scss">
.system-config {
    // 图片上传器样式
    .image-uploader {
        :deep(.el-upload) {
            border: 1px dashed var(--ink-200);
            border-radius: 6px;
            cursor: pointer;
            position: relative;
            overflow: hidden;
            transition: border-color 0.2s;
            width: 120px;
            height: 120px;
            display: flex;
            align-items: center;
            justify-content: center;

            &:hover {
                border-color: var(--brand-500);
            }
        }

        .uploaded-image {
            width: 120px;
            height: 120px;
            object-fit: cover;
            display: block;
        }

        .image-uploader-icon {
            font-size: 28px;
            color: var(--ink-400);
        }
    }

    .upload-tip {
        font-size: 12px;
        color: var(--ink-500);
        margin-top: 8px;
        line-height: 1.4;
    }
}
</style>
