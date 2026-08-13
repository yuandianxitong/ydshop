/** 是否已选择头像（已上传 path 或本地预览均可） */
export function hasAvatar(avatarPath: string, avatarPreview: string): boolean {
  return avatarPath !== '' || avatarPreview !== ''
}

/**
 * 返回尚未完成的必填项文案（按提示顺序：头像 → 昵称 → 手机号）
 * 空数组表示可提交
 */
export function getMissingFields(
  nickname: string,
  avatarPath: string,
  avatarPreview: string,
  showPhone: boolean,
  phoneDisplay: string,
): string[] {
  const missing: string[] = []
  if (!hasAvatar(avatarPath, avatarPreview)) missing.push('头像')
  if (nickname.trim() === '') missing.push('昵称')
  if (showPhone && phoneDisplay === '') missing.push('手机号')
  return missing
}

/** 保存按钮逻辑可用：非 loading 且三项均已具备（头像可为待上传预览） */
export function canSubmit(
  nickname: string,
  avatarPath: string,
  avatarPreview: string,
  showPhone: boolean,
  phoneDisplay: string,
  loading: boolean,
): boolean {
  if (loading) return false
  return getMissingFields(nickname, avatarPath, avatarPreview, showPhone, phoneDisplay).length === 0
}

/** submit 载荷：avatar 为空不带键（后端 request->only 语义，空串会覆盖已有头像） */
export function buildSubmitPayload(nickname: string, avatarPath: string): { nickname: string; avatar?: string } {
  const payload: { nickname: string; avatar?: string } = { nickname: nickname.trim() }
  if (avatarPath !== '') payload.avatar = avatarPath
  return payload
}

/** 手机号 344 脱敏；非 11 位原样返回 */
export function maskMobile(mobile: string): string {
  if (mobile.length !== 11) return mobile
  return `${mobile.slice(0, 3)}****${mobile.slice(7)}`
}
