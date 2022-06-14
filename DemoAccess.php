<?
use Bitrix\Main\Type\DateTime;

class DemoAccess {
    private $demoGroup = 15;
    private $passedLectures = 40;
    private $passedExams = 42;
    public $demoGroupLearning = [456165, 456182];
    private $demoGroupLearning_26 = 456182;
    private $email;
    private $phone;
    private $arImage;
    private $groups = array(3, 5, 10, 4, 15);
    private $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789_';

    public function __construct($email = '', $phone = '', $name = '')
    {
        \Bitrix\Main\Loader::includeModule('iblock');
        $this->email = $email;
        $this->name = $name;
        $this->phone = $phone;
        $this->arImage = CFile::MakeFileArray($_SERVER["DOCUMENT_ROOT"] . "/local/templates/main/img/default_men.jpg");
        $this->arImage["MODULE_ID"] = "main";
    }

    private function createLogin() {
        return 'demo-' . substr(str_shuffle($this->alphabet), 0, 6);
    }

    private function createPassword() {
        return substr(str_shuffle($this->alphabet), 0, 6);
    }

    public function createUser() {
        $password = $this->createPassword();
        $login = $this->createLogin();
        $user = new CUser;
        $arFields = array(
            "NAME" => "Demo",
            "LAST_NAME" => $this->name,
            "EMAIL" => $this->email,
            "LOGIN" => $login,
            "LID" => "ru",
            "ACTIVE" => "Y",
            "GROUP_ID" => $this->groups,
            "PASSWORD" => $password,
            "CONFIRM_PASSWORD" => $password,
            "PERSONAL_PHOTO" => $this->arImage,
            "PERSONAL_PHONE" => $this->phone
        );
        if($ID = $user->Add($arFields)) {
            foreach ($this->demoGroupLearning as $id) {
                s($id);
                $prop = $this->getGroupLearning($id);
                $prop['USER'][] = $ID;
                s($prop);
                $this->setGroupLearning($id, $prop);
            }

            return ['ID' => $ID, 'LOGIN' => $login, 'PASSWORD' => $password];
        }else {
            return $user->LAST_ERROR;
        }
    }

    public function deleteElement($iblock, $idUser) {
        $el = new CIBlockElement;
        $ob = $el->GetList([], ['IBLOCK_ID' => $iblock, 'PROPERTY_USER' => $idUser], false, false, ['ID','IBLOCK_ID','NAME']);
        while ($res = $ob->GetNext(true, false)) {
            $el->delete($res['ID']);
        }
    }

    public function validateUser($id) {
        $rsUser = CUser::GetByID($id);
        $arUser = $rsUser->Fetch();
        if(in_array($this->demoGroup, CUser::GetUserGroup($id))) {
            $objDateTime = new DateTime($arUser['DATE_REGISTER']);
            $endDemo = $objDateTime->getTimestamp() + 3600*24*30;
//            echo "Дата регистрации" . ($objDateTime->getTimestamp()).'<br>';
//            echo "Дата регистрации " . ($objDateTime->toString()).'<br>';
//            echo "Дата конца" . $endDemo.'<br>';
            echo "Дата закрытия Demo доступа " . DateTime::createFromTimestamp($endDemo)->toString().'<br>';
            if(time() >= $endDemo) {

                foreach ($this->demoGroupLearning as $val) {
                    $props = $this->getGroupLearning($val);
                    $props['USER'] = array_diff($props['USER'], [$id]);
                    $this->setGroupLearning($val, $props);
                }
                $this->deleteElement($this->passedLectures, $id);
                $this->deleteElement($this->passedExams, $id);

                if (CUser::Delete($id)) echo "Срок Demo доступа вышел.";
            }
        }
    }

    public function getGroupLearning($id) {
        $props = [];
        $elements = \Bitrix\Iblock\Elements\ElementGroupTable::getList([
            'select' => ['ID', 'NAME', 'USER', 'PROGRAM', 'GROUP'],
            'filter' => ['=ACTIVE' => 'Y', 'ID' => $id],
        ])->fetchAll();
        foreach ($elements as $element) {
            $props['USER'][] = $element['IBLOCK_ELEMENTS_ELEMENT_GROUP_USER_VALUE'];
            $props['PROGRAM'] = $element['IBLOCK_ELEMENTS_ELEMENT_GROUP_PROGRAM_IBLOCK_GENERIC_VALUE'];
            $props['GROUP'] = $element['IBLOCK_ELEMENTS_ELEMENT_GROUP_GROUP_IBLOCK_GENERIC_VALUE'];
        }
        return $props;
    }

    public function setGroupLearning($id, $props) {
        $group = new CIBlockElement;
        $arLoadProductArray = Array(
            "PROPERTY_VALUES"=> $props,
        );
        $group->Update($id, $arLoadProductArray, true);
    }
}