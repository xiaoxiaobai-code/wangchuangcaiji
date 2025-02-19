protected $append = [
    'expire_text'
];

// 获取有效期文本
public function getExpireTextAttr($value, $data)
{
    if ($data['expire_time'] == 0) {
        return '永久有效';
    }
    return date('Y-m-d H:i:s', $data['expire_time']);
} 