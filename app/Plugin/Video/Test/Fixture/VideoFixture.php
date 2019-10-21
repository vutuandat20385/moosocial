<?php
class VideoFixture extends CakeTestFixture {

    public $import = array('model'=>'Video.Video');
    public function init() {
        $this->records = array(
            array(
                'id' => 1,
                'user_id' => 1,
                'category_id' => 4,
                'title' => 'Resident Evil',
                'description' => 'Resident evil des',
                'thumb' => '',
                'source' => 'youtube',
                'source_id' => 'nHPBofJZIiw',
                'created' => date('Y-m-d H:i:s'),
                'privacy' => 1,
                'group_id' => 0
            ),
        );
        parent::init();
    }
    // Optional.
    // Set this property to load fixtures to a different test datasource
    /*public $useDbConfig = 'test';
    public $fields = array(
        'id' => array('type' => 'integer', 'key' => 'primary'),
        'user_id' => array('type' => 'integer'),
        'category_id' => array('type' => 'integer'),
        'title' => array(
            'type' => 'string',
            'length' => 255,
            'null' => false
        ),
        'description' => 'text',
        'thumb' => array(
            'type' => 'string',
            'length' => 255,
            'null' => false
        ),
        'source' => array(
            'type' => 'string',
            'length' => 10,
            'null' => false
        ),
        'source_id' => array(
            'type' => 'string',
            'length' => 100,
            'null' => false
        ),
        'like_count' => array(
            'type' => 'integer',
        ),
        'dislike_count' => array(
            'type' => 'integer',
        ),
        'created' => 'datetime',
        'privacy' => array(
            'type' => 'integer',
        ),
        'group_id' => array(
            'type' => 'integer',
        ),

    );
    public $records = array(
        array(
            'id' => 1,
            'user_id' => 1,
            'category_id' => 0,
            'title' => 'YouTube Rewind: Turn Down for 2014 d',
            'description' => "YouTube Rewind 2014. Celebrating the moments, memes, and people that made 2014. #YouTubeRewind
WATCH 2014â€™S TOP VIDS: http://yt.be/rewind
WATCH THE BTS: https://youtu.be/8sPUM6QnTOI
Music mixed by DJ Earworm: http://youtube.com/djearworm
BEST IN HD!

Action Movie Kid https://youtube.com/theActionMovieKid
Aichi Ono https://youtube.com/SpinboyAichi0307
Amanda Steele https://youtube.com/MakeupbyMandy24
AmazingPhil https://youtube.com/AmazingPhil
Andy Raconte https://youtube.com/AndyRaconte
Anil B https://youtube.com/WaRTeKGaminG
Ape Crime https://youtube.com/ApeCrimeReloaded
Apollos Hester http://youtu.be/X7ymriMhoj0
Barely Political https://youtube.com/barelypolitical
Bart Baker https://youtube.com/BartBaKer
Bethany Mota https://youtube.com/Macbarbie07
Big Bird https://youtube.com/SesameStreet
Bilingirl https://youtube.com/cyoshida1231
Brett Nichols https://youtube.com/BrettNicholsOfficial
Brittani Louise Taylor: https://www.youtube.com/BrittaniLouiseTaylor
Carrie Fletcher https://youtube.com/ItsWayPastMyBedTime
Chris Hardwick https://youtube.com/Nerdist/
Colin Furze https://youtube.com/colinfurze
Conan O'Brien https://youtube.com/teamcoco
Conchita Wurst https://youtube.com/ConchitaWurst
Connor Franta https://youtube.com/ConnorFranta
Corridor Digital https://youtube.com/CorridorDigital
Cyprien https://youtube.com/MonsieurDream
daaruum https://youtube.com/daaruum
danisnotonfire https://youtube.com/danisnotonfire
Devil Baby https://youtube.com/devilsduenyc
Dodie Clark https://youtube.com/doddleoddle
Ella Caney-Willis https://youtube.com/EllaSaysHiya
Enjoy Phoenix https://youtube.com/EnjoyPhoenix
Epic Rap Battles https://youtube.com/ERB
Evan Edinger https://youtube.com/naveregnide
fouseyTUBE https://youtube.com/fouseyTUBE
Freddie W https://youtube.com/freddiew
Gabriel Valenciano https://youtube.com/iamgabvalenciano
Gal Volinez http://goo.gl/zPKRNo
Grace Helbig https://youtube.com/graciehinabox
Hajime https://youtube.com/0214mex
Hannah Hart https://youtube.com/MyHarto
Heart https://youtube.com/ThatsHeart
Hello Denizen https://youtube.com/HelloDenizen
Hikakin https://youtube.com/HIKAKIN
HolaSoyGerman https://youtube.com/HolaSoyGerman
How It Should Have Ended https://youtube.com/HISHEdotcom
IISuperwomanII https://youtube.com/IISuperwomanII
iJustine https://youtube.com/ijustine
Ingrid Nilsen https://youtube.com/missglamorazzi
iTakahashi https://youtube.com/iTakahashikun
JennXPenn https://youtube.com/jennxpenn.
Jenna Marbles https://youtube.com/JennaMarbles
Jimmy Kimmel https://youtube.com/JimmyKimmelLive
John Oliver https://youtube.com/LastWeekTonight
Kacy Catanzaro http://youtu.be/XfZFuw7a13E
Kid President http://goo.gl/D9e40D
Kingsley https://youtube.com/ItsKingsleyBitch
Kosuke https://youtube.com/user/pazudoraya
Kurt Hugo Schneider https://youtube.com/KurtHugoSchneider
Le Floid https://youtube.com/LeFloid
Luke Cutforth https://youtube.com/LukeIsNotSexy
Mamiruton https://youtube.com/TheMaxMurai
Manako (Q'ulle) http://goo.gl/EtLTpW
MasuoTV https://youtube.com/MasuoTV
Matt Bittner http://youtu.be/8UoJ-34Ssa0
Max Murai https://youtube.com/TheMaxMurai
Michelle Phan https://youtube.com/MichellePhan
Mika Shindate https://youtube.com/shindatemika
Niki Albon https://youtube.com/NikiNSammy
PDS https://youtube.com/PDSKabushikiGaisha
Pentatonix https://youtube.com/PTXofficial
PewDiePie https://youtube.com/PewDiePie
PrankvsPrank https://youtube.com/PrankvsPrank
Raphael Gomes https://youtube.com/ItsRaphaBlueBerry
Rhett & Link https://youtube.com/RhettandLink
Rosanna Pansino https://youtube.com/RosannaPansino
Sadie Miller https://youtube.com/amillerfull
Sam Tsui https://youtube.com/TheSamTsui
Sami Slimani https://youtube.com/HerrTutorial
Sammy Albon https://youtube.com/NikiNSammy
Sasaki Asahi https://youtube.com/sasakiasahi
Seikin https://youtube.com/SeikinTV
Sione Vaka Kelepi https://youtube.com/sionemaraschino
Sir Fedora https://www.youtube.com/SirFedora
SkyDoesMinecraft https://youtube.com/SkyDoesMinecraft
Smosh https://youtube.com/smosh
Stephen Colbert https://youtube.com/comedycentral
Steve Kardynal https://youtube.com/SteveKardynal
Stuart Edge https://youtube.com/stuartedge
The Fine Bros https://youtube.com/TheFineBros
The Gregory Brothers https://youtube.com/schmoyoho
The Slow Mo Guys https://youtube.com/theslowmoguys
Troye Sivan https://youtube.com/TroyeSivan18
Tyler Oakley https://youtube.com/tyleroakley
VlogBrothers https://youtube.com/vlogbrothers
Vsauce2 https://youtube.com/Vsauce2
Vsauce3 https://youtube.com/Vsauce3
WORLD ORDER https://youtube.com/crnaviofficial

Rewind 2014 created by YouTube & Portal A
Full credits: http://portal-a.com/rewind2014

Special thanks to Koda, the beloved pup of Rewind's lead editor, who made her debut as Spider Dog.

Help fight ALS: http://goo.gl/aLCckj",
            'thumb' => 'cd604ac0d96a6a24fc4d90feaa2478ce.jpg',
            'source' => 'youtube',
            'source_id' => 'zKx2B8WCQuw',
            'like_count' => 0,
            'dislike_count' => 0,
            'created' => '2014-12-11 08:31:20.000000',
            'privacy' => 1,
            'group_id' => 1,
            'group_id' => 1,
        )
    );*/

}