<?php
define( 'G5_VERSION', '0.2.2' );
define( 'G5_NAME', 'gnupress' );
define( 'G5_DEBUG', false );
define( 'G5_OPTION_KEY', 'g5_options' );

//메타키 정의
define( 'G5_META_KEY', G5_NAME.'_meta' );
define( 'G5_META_TYPE', 'g5_wr' );
define( 'G5_FILE_META_KEY', 'g5_file_data' );

// 퍼미션
define('G5_DIR_PERMISSION',  0755); // 디렉토리 생성시 퍼미션
define('G5_FILE_PERMISSION', 0644); // 파일 생성시 퍼미션

// 이 상수가 정의되지 않으면 각각의 개별 페이지는 별도로 실행될 수 없음
define('_GNUBOARD_', true);

// 게시판에서 링크의 기본개수를 말합니다.
// 필드를 추가하면 이 숫자를 필드수에 맞게 늘려주십시오.
define('G5_LINK_COUNT', 2);
define('G5_IP_DISPLAY', '\\1.♡.\\3.\\4');

// 경로 상수
define( 'G5_DIR_URL',  plugin_dir_url ( __FILE__ ) );
define( 'G5_DIR_PATH', plugin_dir_path( __FILE__ ) );
define( 'G5_SKIN_PATH', G5_DIR_PATH.'skin' );
define( 'G5_PLUGIN_PATH', G5_DIR_PATH.'plugin' );
define( 'G5_PLUGIN_URL', G5_DIR_URL.'plugin' );
//아래 단어가 포함된 내용은 게시할 수 없습니다. 단어와 단어 사이는 ,로 구분합니다.
define( 'G5_CF_FILTER', '18아,18놈,18새끼,18년,18뇬,18노,18것,18넘,개년,개놈,개뇬,개새,개색끼,개세끼,개세이,개쉐이,개쉑,개쉽,개시키,개자식,개좆,게색기,게색끼,광뇬,뇬,눈깔,뉘미럴,니귀미,니기미,니미,도촬,되질래,뒈져라,뒈진다,디져라,디진다,디질래,병쉰,병신,뻐큐,뻑큐,뽁큐,삐리넷,새꺄,쉬발,쉬밸,쉬팔,쉽알,스패킹,스팽,시벌,시부랄,시부럴,시부리,시불,시브랄,시팍,시팔,시펄,실밸,십8,십쌔,십창,싶알,쌉년,썅놈,쌔끼,쌩쑈,썅,써벌,썩을년,쎄꺄,쎄엑,쓰바,쓰발,쓰벌,쓰팔,씨8,씨댕,씨바,씨발,씨뱅,씨봉알,씨부랄,씨부럴,씨부렁,씨부리,씨불,씨브랄,씨빠,씨빨,씨뽀랄,씨팍,씨팔,씨펄,씹,아가리,아갈이,엄창,접년,잡놈,재랄,저주글,조까,조빠,조쟁이,조지냐,조진다,조질래,존나,존니,좀물,좁년,좃,좆,좇,쥐랄,쥐롤,쥬디,지랄,지럴,지롤,지미랄,쫍빱,凸,퍽큐,뻑큐,빠큐,ㅅㅂㄹㅁ');
?>