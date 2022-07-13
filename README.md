## 对 phpunit 进行了哪些修改
  - 去掉了哪些文件夹
    - php-code-coverage
    - phpdocumentor-reflection-docblock
    - php-invoker
    - phpspec-prophecy
    - php-token-stream
    - phpunit-selenium
    - sebastian-global-state
    - symfony

  - namespace 相关调整
    - 去掉了 class 开始部分的 namespace SebastianBergmann/Diff, use SebastianBergmann/Diff
    - class 命名由 class Diff {} 调整为 class SebastianBergmann_Diff_Diff {}

  - Reflection 类调整
