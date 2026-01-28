<?php
/**
 * Name Generator Service
 * Generates meaningful email usernames (English/Vietnamese + numbers)
 * Format: firstnamelastname + 2-3 digit number (NO DOTS)
 */
class NameGenerator
{
    private static array $englishFirstNames = [
        'james',
        'john',
        'robert',
        'michael',
        'william',
        'david',
        'richard',
        'joseph',
        'thomas',
        'charles',
        'daniel',
        'matthew',
        'anthony',
        'mark',
        'donald',
        'steven',
        'paul',
        'andrew',
        'joshua',
        'kenneth',
        'kevin',
        'brian',
        'george',
        'edward',
        'ronald',
        'timothy',
        'jason',
        'jeffrey',
        'ryan',
        'jacob',
        'gary',
        'nicholas',
        'eric',
        'jonathan',
        'stephen',
        'larry',
        'justin',
        'scott',
        'brandon',
        'benjamin',
        'mary',
        'patricia',
        'jennifer',
        'linda',
        'barbara',
        'elizabeth',
        'susan',
        'jessica',
        'sarah',
        'karen',
        'nancy',
        'lisa',
        'betty',
        'margaret',
        'sandra',
        'ashley',
        'kimberly',
        'emily',
        'donna',
        'michelle',
        'dorothy',
        'carol',
        'amanda',
        'melissa',
        'deborah',
        'stephanie',
        'rebecca',
        'sharon',
        'laura',
        'cynthia',
        'kathleen',
        'amy',
        'angela',
        'shirley',
        'anna',
        'brenda',
        'pamela',
        'emma',
        'nicole',
        'helen'
    ];

    private static array $englishLastNames = [
        'smith',
        'johnson',
        'williams',
        'brown',
        'jones',
        'garcia',
        'miller',
        'davis',
        'rodriguez',
        'martinez',
        'hernandez',
        'lopez',
        'gonzalez',
        'wilson',
        'anderson',
        'thomas',
        'taylor',
        'moore',
        'jackson',
        'martin',
        'lee',
        'perez',
        'thompson',
        'white',
        'harris',
        'sanchez',
        'clark',
        'ramirez',
        'lewis',
        'robinson',
        'walker',
        'young',
        'allen',
        'king',
        'wright',
        'scott',
        'torres',
        'nguyen',
        'hill',
        'flores',
        'green',
        'adams',
        'nelson',
        'baker',
        'hall',
        'rivera',
        'campbell',
        'mitchell',
        'carter',
        'roberts',
        'gomez',
        'phillips',
        'evans',
        'turner',
        'diaz',
        'parker',
        'cruz',
        'edwards',
        'collins',
        'reyes',
        'stewart',
        'morris',
        'morales',
        'murphy'
    ];

    private static array $vietnameseFirstNames = [
        'an',
        'anh',
        'bao',
        'binh',
        'cuong',
        'dung',
        'duc',
        'hai',
        'hieu',
        'hoa',
        'hoang',
        'hung',
        'huong',
        'huy',
        'khanh',
        'khoa',
        'lan',
        'linh',
        'long',
        'mai',
        'minh',
        'nam',
        'nga',
        'nhan',
        'nhung',
        'phuong',
        'quan',
        'quang',
        'quynh',
        'son',
        'tam',
        'thanh',
        'thao',
        'thi',
        'thuy',
        'tien',
        'trinh',
        'trung',
        'tu',
        'tuan',
        'tuyet',
        'van',
        'viet',
        'vu',
        'xuan',
        'yen'
    ];

    private static array $vietnameseLastNames = [
        'nguyen',
        'tran',
        'le',
        'pham',
        'hoang',
        'phan',
        'vu',
        'vo',
        'dang',
        'bui',
        'do',
        'ho',
        'ngo',
        'duong',
        'ly',
        'dinh',
        'mai',
        'truong',
        'cao',
        'trinh',
        'ta',
        'lam',
        'huynh',
        'luong',
        'ha',
        'tong',
        'quach',
        'chu',
        'bach',
        'hien',
        'huy',
        'tam',
        'tri',
        'tai',
        'tien',
        'tinh',
        'tuan',
        'tuyen',
        'tung',
        'tiang',
        'tinh',
        'tuan',
        'tuyen',
        'tung',
        'tuong',
        'thai'
    ];

    /**
     * Generate random meaningful username
     * Format: firstnamelastname + random number (NO DOTS)
     */
    public static function generateUsername(string $type = 'random'): string
    {
        // Normalize type
        if ($type === 'vn' || $type === 'vietnam') {
            $useEnglish = false;
        } elseif ($type === 'en' || $type === 'english') {
            $useEnglish = true;
        } else {
            $useEnglish = random_int(0, 1) === 1;
        }

        if ($useEnglish) {
            $firstName = self::$englishFirstNames[array_rand(self::$englishFirstNames)];
            $lastName = self::$englishLastNames[array_rand(self::$englishLastNames)];
        } else {
            $firstName = self::$vietnameseFirstNames[array_rand(self::$vietnameseFirstNames)];
            $lastName = self::$vietnameseLastNames[array_rand(self::$vietnameseLastNames)];
        }

        $number = random_int(10, 999);
        return $firstName . $lastName . $number;
    }

    /**
     * Get name type from generated username
     */
    public static function getNameType(string $type = 'random'): string
    {
        if ($type === 'vn' || $type === 'vietnam')
            return 'vn';
        if ($type === 'en' || $type === 'english')
            return 'en';
        return random_int(0, 1) === 1 ? 'en' : 'vn';
    }
}
