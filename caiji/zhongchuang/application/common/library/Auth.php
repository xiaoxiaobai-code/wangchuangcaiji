/**
 * 注册用户
 *
 * @param string $username  用户名
 * @param string $password  密码
 * @param string $email     邮箱
 * @param string $mobile    手机号
 * @param array  $extend    扩展参数
 * @return boolean
 */
public function register($username, $password, $email = '', $mobile = '', $extend = [])
{
    // 检查是否开启注册
    if (!Config::get('fastadmin.user_registration')) {
        $this->setError('注册已关闭');
        return false;
    }
    
    // 验证用户名、邮箱、手机号是否已存在
    if (User::getByUsername($username)) {
        $this->setError('用户名已经存在');
        return false;
    }
    if ($email && User::getByEmail($email)) {
        $this->setError('邮箱已经存在');
        return false;
    }
    if ($mobile && User::getByMobile($mobile)) {
        $this->setError('手机号已经存在');
        return false;
    }

    $ip = request()->ip();
    $time = time();

    $data = [
        'username' => $username,
        'password' => $password,
        'email'    => $email,
        'mobile'   => $mobile,
        'level'    => 1,
        'score'    => 0,
        'avatar'   => '',
        'jointime' => $time,
        'joinip'   => $ip,
        'logintime' => $time,
        'loginip'   => $ip,
        'prevtime'  => $time,
        'status'    => 'normal'
    ];
    
    // 合并扩展字段
    $data = array_merge($data, $extend);

    // 密码加密
    $data['salt'] = Random::alnum();
    $data['password'] = $this->getEncryptPassword($password, $data['salt']);

    // 创建用户
    $user = User::create($data);
    if ($user) {
        $this->_user = $user;
        return true;
    }
    return false;
} 