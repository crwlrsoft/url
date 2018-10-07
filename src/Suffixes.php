<?php

namespace Crwlr\Url;

use Crwlr\Url\Lists\Store;
use TrueBV\Punycode;

/**
 * Class Suffixes
 *
 * This class gives access to all the public domain suffixes from the Public Suffix List.
 * https://publicsuffix.org/
 */

class Suffixes extends Store
{
    /**
     * @var string
     */
    protected $storeFilename = 'suffixes.php';

    /**
     * @var Punycode
     */
    private $punyCode;

    /**
     * The full list of all public suffixes is pretty big (currently over 8000 suffixes) and always loading
     * and performing lookups in the full list may unnecessarily slow things a bit down. So I found this list
     * of usage of TLDs https://w3techs.com/technologies/overview/top_level_domain/all
     * In /bin/fast-suffixes is a script that generates the list below with all top level domain suffixes and common
     * second level (gov, co, com,...) for these suffixes, that account for more than 0.1% of all websites according
     * to the w3techs.com usage ranking.
     *
     * @var array
     */
    protected $fallbackList = ['com.ac'=>0,'edu.ac'=>0,'gov.ac'=>0,'net.ac'=>0,'mil.ac'=>0,'org.ac'=>0,'ae'=>0,'co.ae'=>0,'net.ae'=>0,'org.ae'=>0,'ac.ae'=>0,'gov.ae'=>0,'mil.ae'=>0,'gov.af'=>0,'com.af'=>0,'org.af'=>0,'net.af'=>0,'edu.af'=>0,'com.ag'=>0,'org.ag'=>0,'net.ag'=>0,'co.ag'=>0,'com.ai'=>0,'net.ai'=>0,'org.ai'=>0,'com.al'=>0,'edu.al'=>0,'gov.al'=>0,'mil.al'=>0,'net.al'=>0,'org.al'=>0,'co.ao'=>0,'ar'=>0,'com.ar'=>0,'edu.ar'=>0,'gov.ar'=>0,'mil.ar'=>0,'net.ar'=>0,'org.ar'=>0,'gov.as'=>0,'at'=>0,'ac.at'=>0,'co.at'=>0,'au'=>0,'com.au'=>0,'net.au'=>0,'org.au'=>0,'edu.au'=>0,'gov.au'=>0,'com.aw'=>0,'az'=>0,'com.az'=>0,'net.az'=>0,'gov.az'=>0,'org.az'=>0,'edu.az'=>0,'mil.az'=>0,'com.ba'=>0,'edu.ba'=>0,'gov.ba'=>0,'mil.ba'=>0,'net.ba'=>0,'org.ba'=>0,'co.bb'=>0,'com.bb'=>0,'edu.bb'=>0,'gov.bb'=>0,'net.bb'=>0,'org.bb'=>0,'be'=>0,'ac.be'=>0,'gov.bf'=>0,'bg'=>0,'com.bh'=>0,'edu.bh'=>0,'net.bh'=>0,'org.bh'=>0,'gov.bh'=>0,'co.bi'=>0,'com.bi'=>0,'edu.bi'=>0,'org.bi'=>0,'biz'=>0,'com.bm'=>0,'edu.bm'=>0,'gov.bm'=>0,'net.bm'=>0,'org.bm'=>0,'com.bo'=>0,'edu.bo'=>0,'org.bo'=>0,'net.bo'=>0,'mil.bo'=>0,'br'=>0,'com.br'=>0,'edu.br'=>0,'gov.br'=>0,'mil.br'=>0,'net.br'=>0,'org.br'=>0,'com.bs'=>0,'net.bs'=>0,'org.bs'=>0,'edu.bs'=>0,'gov.bs'=>0,'com.bt'=>0,'edu.bt'=>0,'gov.bt'=>0,'net.bt'=>0,'org.bt'=>0,'co.bw'=>0,'org.bw'=>0,'by'=>0,'gov.by'=>0,'mil.by'=>0,'com.by'=>0,'com.bz'=>0,'net.bz'=>0,'org.bz'=>0,'edu.bz'=>0,'gov.bz'=>0,'ca'=>0,'cat'=>0,'cc'=>0,'gov.cd'=>0,'ch'=>0,'org.ci'=>0,'com.ci'=>0,'co.ci'=>0,'edu.ci'=>0,'ac.ci'=>0,'net.ci'=>0,'cl'=>0,'gov.cl'=>0,'co.cl'=>0,'mil.cl'=>0,'co.cm'=>0,'com.cm'=>0,'gov.cm'=>0,'net.cm'=>0,'cn'=>0,'ac.cn'=>0,'com.cn'=>0,'edu.cn'=>0,'gov.cn'=>0,'net.cn'=>0,'org.cn'=>0,'mil.cn'=>0,'com.co'=>0,'edu.co'=>0,'gov.co'=>0,'mil.co'=>0,'net.co'=>0,'org.co'=>0,'com'=>0,'ac.cr'=>0,'co.cr'=>0,'com.cu'=>0,'edu.cu'=>0,'org.cu'=>0,'net.cu'=>0,'gov.cu'=>0,'com.cw'=>0,'edu.cw'=>0,'net.cw'=>0,'org.cw'=>0,'gov.cx'=>0,'ac.cy'=>0,'com.cy'=>0,'gov.cy'=>0,'net.cy'=>0,'org.cy'=>0,'cz'=>0,'de'=>0,'dk'=>0,'com.dm'=>0,'net.dm'=>0,'org.dm'=>0,'edu.dm'=>0,'gov.dm'=>0,'com.do'=>0,'edu.do'=>0,'gov.do'=>0,'mil.do'=>0,'net.do'=>0,'org.do'=>0,'com.dz'=>0,'org.dz'=>0,'net.dz'=>0,'gov.dz'=>0,'edu.dz'=>0,'com.ec'=>0,'net.ec'=>0,'org.ec'=>0,'edu.ec'=>0,'gov.ec'=>0,'mil.ec'=>0,'edu'=>0,'ee'=>0,'edu.ee'=>0,'gov.ee'=>0,'com.ee'=>0,'org.ee'=>0,'com.eg'=>0,'edu.eg'=>0,'gov.eg'=>0,'mil.eg'=>0,'net.eg'=>0,'org.eg'=>0,'es'=>0,'com.es'=>0,'org.es'=>0,'edu.es'=>0,'com.et'=>0,'gov.et'=>0,'org.et'=>0,'edu.et'=>0,'net.et'=>0,'eu'=>0,'fi'=>0,'fr'=>0,'com.fr'=>0,'com.ge'=>0,'edu.ge'=>0,'gov.ge'=>0,'org.ge'=>0,'mil.ge'=>0,'net.ge'=>0,'co.gg'=>0,'net.gg'=>0,'org.gg'=>0,'com.gh'=>0,'edu.gh'=>0,'gov.gh'=>0,'org.gh'=>0,'mil.gh'=>0,'com.gi'=>0,'gov.gi'=>0,'edu.gi'=>0,'org.gi'=>0,'co.gl'=>0,'com.gl'=>0,'edu.gl'=>0,'net.gl'=>0,'org.gl'=>0,'ac.gn'=>0,'com.gn'=>0,'edu.gn'=>0,'gov.gn'=>0,'org.gn'=>0,'net.gn'=>0,'com.gp'=>0,'net.gp'=>0,'edu.gp'=>0,'org.gp'=>0,'gr'=>0,'com.gr'=>0,'edu.gr'=>0,'net.gr'=>0,'org.gr'=>0,'gov.gr'=>0,'com.gt'=>0,'edu.gt'=>0,'mil.gt'=>0,'net.gt'=>0,'org.gt'=>0,'co.gy'=>0,'com.gy'=>0,'edu.gy'=>0,'gov.gy'=>0,'net.gy'=>0,'org.gy'=>0,'hk'=>0,'com.hk'=>0,'edu.hk'=>0,'gov.hk'=>0,'net.hk'=>0,'org.hk'=>0,'com.hn'=>0,'edu.hn'=>0,'org.hn'=>0,'net.hn'=>0,'mil.hn'=>0,'hr'=>0,'com.hr'=>0,'com.ht'=>0,'net.ht'=>0,'org.ht'=>0,'edu.ht'=>0,'hu'=>0,'co.hu'=>0,'org.hu'=>0,'id'=>0,'ac.id'=>0,'co.id'=>0,'mil.id'=>0,'net.id'=>0,'ie'=>0,'gov.ie'=>0,'il'=>0,'ac.il'=>0,'co.il'=>0,'gov.il'=>0,'net.il'=>0,'org.il'=>0,'ac.im'=>0,'co.im'=>0,'com.im'=>0,'net.im'=>0,'org.im'=>0,'in'=>0,'co.in'=>0,'net.in'=>0,'org.in'=>0,'ac.in'=>0,'edu.in'=>0,'gov.in'=>0,'mil.in'=>0,'info'=>0,'io'=>0,'com.io'=>0,'gov.iq'=>0,'edu.iq'=>0,'mil.iq'=>0,'com.iq'=>0,'org.iq'=>0,'net.iq'=>0,'ir'=>0,'ac.ir'=>0,'co.ir'=>0,'gov.ir'=>0,'net.ir'=>0,'org.ir'=>0,'net.is'=>0,'com.is'=>0,'edu.is'=>0,'gov.is'=>0,'org.is'=>0,'it'=>0,'gov.it'=>0,'edu.it'=>0,'co.it'=>0,'co.je'=>0,'net.je'=>0,'org.je'=>0,'com.jo'=>0,'org.jo'=>0,'net.jo'=>0,'edu.jo'=>0,'gov.jo'=>0,'mil.jo'=>0,'jp'=>0,'ac.jp'=>0,'co.jp'=>0,'ac.ke'=>0,'co.ke'=>0,'org.kg'=>0,'net.kg'=>0,'com.kg'=>0,'edu.kg'=>0,'gov.kg'=>0,'mil.kg'=>0,'edu.ki'=>0,'net.ki'=>0,'org.ki'=>0,'gov.ki'=>0,'com.ki'=>0,'org.km'=>0,'gov.km'=>0,'edu.km'=>0,'mil.km'=>0,'com.km'=>0,'net.kn'=>0,'org.kn'=>0,'edu.kn'=>0,'gov.kn'=>0,'com.kp'=>0,'edu.kp'=>0,'gov.kp'=>0,'org.kp'=>0,'kr'=>0,'ac.kr'=>0,'co.kr'=>0,'mil.kr'=>0,'edu.ky'=>0,'gov.ky'=>0,'com.ky'=>0,'org.ky'=>0,'net.ky'=>0,'kz'=>0,'org.kz'=>0,'edu.kz'=>0,'net.kz'=>0,'gov.kz'=>0,'mil.kz'=>0,'com.kz'=>0,'net.la'=>0,'edu.la'=>0,'gov.la'=>0,'com.la'=>0,'org.la'=>0,'com.lb'=>0,'edu.lb'=>0,'gov.lb'=>0,'net.lb'=>0,'org.lb'=>0,'com.lc'=>0,'net.lc'=>0,'co.lc'=>0,'org.lc'=>0,'edu.lc'=>0,'gov.lc'=>0,'gov.lk'=>0,'net.lk'=>0,'com.lk'=>0,'org.lk'=>0,'edu.lk'=>0,'ac.lk'=>0,'com.lr'=>0,'edu.lr'=>0,'gov.lr'=>0,'org.lr'=>0,'net.lr'=>0,'co.ls'=>0,'org.ls'=>0,'lt'=>0,'gov.lt'=>0,'lv'=>0,'com.lv'=>0,'edu.lv'=>0,'gov.lv'=>0,'org.lv'=>0,'mil.lv'=>0,'net.lv'=>0,'com.ly'=>0,'net.ly'=>0,'gov.ly'=>0,'edu.ly'=>0,'org.ly'=>0,'co.ma'=>0,'net.ma'=>0,'gov.ma'=>0,'org.ma'=>0,'ac.ma'=>0,'me'=>0,'co.me'=>0,'net.me'=>0,'org.me'=>0,'edu.me'=>0,'ac.me'=>0,'gov.me'=>0,'org.mg'=>0,'gov.mg'=>0,'edu.mg'=>0,'mil.mg'=>0,'com.mg'=>0,'co.mg'=>0,'com.mk'=>0,'org.mk'=>0,'net.mk'=>0,'edu.mk'=>0,'gov.mk'=>0,'com.ml'=>0,'edu.ml'=>0,'gov.ml'=>0,'net.ml'=>0,'org.ml'=>0,'gov.mn'=>0,'edu.mn'=>0,'org.mn'=>0,'com.mo'=>0,'net.mo'=>0,'org.mo'=>0,'edu.mo'=>0,'gov.mo'=>0,'gov.mr'=>0,'com.ms'=>0,'edu.ms'=>0,'gov.ms'=>0,'net.ms'=>0,'org.ms'=>0,'com.mt'=>0,'edu.mt'=>0,'net.mt'=>0,'org.mt'=>0,'com.mu'=>0,'net.mu'=>0,'org.mu'=>0,'gov.mu'=>0,'ac.mu'=>0,'co.mu'=>0,'com.mv'=>0,'edu.mv'=>0,'gov.mv'=>0,'mil.mv'=>0,'net.mv'=>0,'org.mv'=>0,'ac.mw'=>0,'co.mw'=>0,'com.mw'=>0,'edu.mw'=>0,'gov.mw'=>0,'net.mw'=>0,'org.mw'=>0,'mx'=>0,'com.mx'=>0,'org.mx'=>0,'edu.mx'=>0,'net.mx'=>0,'my'=>0,'com.my'=>0,'net.my'=>0,'org.my'=>0,'gov.my'=>0,'edu.my'=>0,'mil.my'=>0,'ac.mz'=>0,'co.mz'=>0,'edu.mz'=>0,'gov.mz'=>0,'mil.mz'=>0,'net.mz'=>0,'org.mz'=>0,'co.na'=>0,'com.na'=>0,'org.na'=>0,'net'=>0,'com.nf'=>0,'net.nf'=>0,'ng'=>0,'com.ng'=>0,'edu.ng'=>0,'gov.ng'=>0,'mil.ng'=>0,'net.ng'=>0,'org.ng'=>0,'ac.ni'=>0,'co.ni'=>0,'com.ni'=>0,'edu.ni'=>0,'mil.ni'=>0,'net.ni'=>0,'org.ni'=>0,'nl'=>0,'no'=>0,'mil.no'=>0,'gov.nr'=>0,'edu.nr'=>0,'org.nr'=>0,'net.nr'=>0,'com.nr'=>0,'nz'=>0,'ac.nz'=>0,'co.nz'=>0,'mil.nz'=>0,'net.nz'=>0,'org.nz'=>0,'co.om'=>0,'com.om'=>0,'edu.om'=>0,'gov.om'=>0,'net.om'=>0,'org.om'=>0,'org'=>0,'ac.pa'=>0,'com.pa'=>0,'org.pa'=>0,'edu.pa'=>0,'net.pa'=>0,'pe'=>0,'edu.pe'=>0,'mil.pe'=>0,'org.pe'=>0,'com.pe'=>0,'net.pe'=>0,'com.pf'=>0,'org.pf'=>0,'edu.pf'=>0,'ph'=>0,'com.ph'=>0,'net.ph'=>0,'org.ph'=>0,'gov.ph'=>0,'edu.ph'=>0,'mil.ph'=>0,'pk'=>0,'com.pk'=>0,'net.pk'=>0,'edu.pk'=>0,'org.pk'=>0,'gov.pk'=>0,'pl'=>0,'com.pl'=>0,'net.pl'=>0,'org.pl'=>0,'edu.pl'=>0,'mil.pl'=>0,'gov.pl'=>0,'gov.pn'=>0,'co.pn'=>0,'org.pn'=>0,'edu.pn'=>0,'net.pn'=>0,'com.pr'=>0,'net.pr'=>0,'org.pr'=>0,'gov.pr'=>0,'edu.pr'=>0,'ac.pr'=>0,'pro'=>0,'edu.ps'=>0,'gov.ps'=>0,'com.ps'=>0,'org.ps'=>0,'net.ps'=>0,'pt'=>0,'net.pt'=>0,'gov.pt'=>0,'org.pt'=>0,'edu.pt'=>0,'com.pt'=>0,'pw'=>0,'co.pw'=>0,'com.py'=>0,'edu.py'=>0,'gov.py'=>0,'mil.py'=>0,'net.py'=>0,'org.py'=>0,'com.qa'=>0,'edu.qa'=>0,'gov.qa'=>0,'mil.qa'=>0,'net.qa'=>0,'org.qa'=>0,'com.re'=>0,'ro'=>0,'com.ro'=>0,'org.ro'=>0,'rs'=>0,'ac.rs'=>0,'co.rs'=>0,'edu.rs'=>0,'gov.rs'=>0,'org.rs'=>0,'ru'=>0,'ac.ru'=>0,'edu.ru'=>0,'gov.ru'=>0,'mil.ru'=>0,'gov.rw'=>0,'net.rw'=>0,'edu.rw'=>0,'ac.rw'=>0,'com.rw'=>0,'co.rw'=>0,'mil.rw'=>0,'com.sa'=>0,'net.sa'=>0,'org.sa'=>0,'gov.sa'=>0,'edu.sa'=>0,'com.sb'=>0,'edu.sb'=>0,'gov.sb'=>0,'net.sb'=>0,'org.sb'=>0,'com.sc'=>0,'gov.sc'=>0,'net.sc'=>0,'org.sc'=>0,'edu.sc'=>0,'com.sd'=>0,'net.sd'=>0,'org.sd'=>0,'edu.sd'=>0,'gov.sd'=>0,'se'=>0,'ac.se'=>0,'org.se'=>0,'sg'=>0,'com.sg'=>0,'net.sg'=>0,'org.sg'=>0,'gov.sg'=>0,'edu.sg'=>0,'com.sh'=>0,'net.sh'=>0,'gov.sh'=>0,'org.sh'=>0,'mil.sh'=>0,'si'=>0,'sk'=>0,'com.sl'=>0,'net.sl'=>0,'edu.sl'=>0,'gov.sl'=>0,'org.sl'=>0,'com.sn'=>0,'edu.sn'=>0,'org.sn'=>0,'com.so'=>0,'net.so'=>0,'org.so'=>0,'co.st'=>0,'com.st'=>0,'edu.st'=>0,'gov.st'=>0,'mil.st'=>0,'net.st'=>0,'org.st'=>0,'su'=>0,'com.sv'=>0,'edu.sv'=>0,'org.sv'=>0,'gov.sx'=>0,'edu.sy'=>0,'gov.sy'=>0,'net.sy'=>0,'mil.sy'=>0,'com.sy'=>0,'org.sy'=>0,'co.sz'=>0,'ac.sz'=>0,'org.sz'=>0,'th'=>0,'ac.th'=>0,'co.th'=>0,'net.th'=>0,'ac.tj'=>0,'co.tj'=>0,'com.tj'=>0,'edu.tj'=>0,'gov.tj'=>0,'mil.tj'=>0,'net.tj'=>0,'org.tj'=>0,'tk'=>0,'gov.tl'=>0,'com.tm'=>0,'co.tm'=>0,'org.tm'=>0,'net.tm'=>0,'gov.tm'=>0,'mil.tm'=>0,'edu.tm'=>0,'com.tn'=>0,'gov.tn'=>0,'net.tn'=>0,'org.tn'=>0,'com.to'=>0,'gov.to'=>0,'net.to'=>0,'org.to'=>0,'edu.to'=>0,'mil.to'=>0,'tr'=>0,'com.tr'=>0,'net.tr'=>0,'org.tr'=>0,'gov.tr'=>0,'mil.tr'=>0,'edu.tr'=>0,'co.tt'=>0,'com.tt'=>0,'org.tt'=>0,'net.tt'=>0,'gov.tt'=>0,'edu.tt'=>0,'tv'=>0,'tw'=>0,'edu.tw'=>0,'gov.tw'=>0,'mil.tw'=>0,'com.tw'=>0,'net.tw'=>0,'org.tw'=>0,'ac.tz'=>0,'co.tz'=>0,'mil.tz'=>0,'ua'=>0,'com.ua'=>0,'edu.ua'=>0,'gov.ua'=>0,'net.ua'=>0,'org.ua'=>0,'co.ug'=>0,'ac.ug'=>0,'com.ug'=>0,'org.ug'=>0,'uk'=>0,'ac.uk'=>0,'co.uk'=>0,'gov.uk'=>0,'net.uk'=>0,'org.uk'=>0,'us'=>0,'co.us'=>0,'com.uy'=>0,'edu.uy'=>0,'mil.uy'=>0,'net.uy'=>0,'org.uy'=>0,'co.uz'=>0,'com.uz'=>0,'net.uz'=>0,'org.uz'=>0,'com.vc'=>0,'net.vc'=>0,'org.vc'=>0,'gov.vc'=>0,'mil.vc'=>0,'edu.vc'=>0,'co.ve'=>0,'com.ve'=>0,'edu.ve'=>0,'gov.ve'=>0,'mil.ve'=>0,'net.ve'=>0,'org.ve'=>0,'co.vi'=>0,'com.vi'=>0,'net.vi'=>0,'org.vi'=>0,'vn'=>0,'com.vn'=>0,'net.vn'=>0,'org.vn'=>0,'edu.vn'=>0,'gov.vn'=>0,'ac.vn'=>0,'com.vu'=>0,'edu.vu'=>0,'net.vu'=>0,'org.vu'=>0,'com.ws'=>0,'net.ws'=>0,'org.ws'=>0,'gov.ws'=>0,'edu.ws'=>0,'рф'=>0,'ac.za'=>0,'co.za'=>0,'edu.za'=>0,'gov.za'=>0,'mil.za'=>0,'net.za'=>0,'org.za'=>0,'ac.zm'=>0,'co.zm'=>0,'com.zm'=>0,'edu.zm'=>0,'gov.zm'=>0,'mil.zm'=>0,'net.zm'=>0,'org.zm'=>0,'ac.zw'=>0,'co.zw'=>0,'gov.zw'=>0,'mil.zw'=>0,'org.zw'=>0,'club'=>0,'download'=>0,'online'=>0,'site'=>0,'top'=>0,'xyz'=>0,'com.de'=>0,'com.se'=>0,'co.com'=>0,'co.ca'=>0,'co.cz'=>0,'co.nl'=>0,'co.no'=>0,'co.dk'=>0,'com.ru'=>0,'co.krd'=>0,'edu.krd'=>0,'co.pl'=>0,'net.ru'=>0,'org.ru'=>0,'co.ua'=>0];

    /**
     * @param null|Punycode $punyCode
     */
    public function __construct($punyCode = null)
    {
        $this->punyCode = ($punyCode instanceof Punycode) ? $punyCode : new Punycode();
        parent::__construct();
    }

    /**
     * @param string $host
     * @return string|null
     */
    public function getByHost($host = '')
    {
        if (Helpers::containsCharactersNotAllowedInHost($host)) {
            $host = $this->punyCode->encode($host);
        }

        foreach ($this->getPossibleSuffixes($host) as $suffix) {
            if ($this->exists($suffix)) {
                return $suffix;
            }
        }

        return null;
    }

    /**
     * @param mixed $key
     * @param bool $idnDecoded
     * @return bool
     */
    public function exists($key, $idnDecoded = false) : bool
    {
        if (!is_string($key)) {
            return false;
        }

        if (parent::exists($key) || $this->wildcardSuffixExists($key)) {
            return true;
        }

        // Not found, maybe $key is an encoded idn domain suffix, so try decoding it.
        if ($idnDecoded === false) {
            $decodedKey = $this->punyCode->decode($key);

            if ($key !== $decodedKey && $this->exists($decodedKey, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $key
     * @return bool
     */
    private function wildcardSuffixExists(string $key)
    {
        $splitAtDot = explode('.', $key);

        if (count($splitAtDot) > 1) {
            $wildcardVersion = '*' . substr($key, strlen($splitAtDot[0]));

            if (isset($this->list[$wildcardVersion])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get all possible suffixes from a host string ordered by length descending.
     * The order is important because if you have for example a .co.uk domain and
     * you search for the shortest possible suffix first (.uk) and return that,
     * that would be wrong.
     *
     * @param string $host
     * @return string[]
     */
    private function getPossibleSuffixes($host = '')
    {
        if (!is_string($host) || $host === '') {
            return [];
        }

        $hostParts = explode('.', strtolower($host));

        if (count($hostParts) < 2) {
            return [];
        }

        $suffix = '';
        $suffixes = [];
        $startAtArrayCount = count($hostParts) - 1;

        for ($i = $startAtArrayCount; $i > 0; $i--) {
            // Concatenate the possible suffixes, piece by piece. Set numeric array keys downwards to 0 for the
            // longest possible suffix, like:
            // sub.domain.example.com = [2 => 'com', 1 => 'example.com', 0 => 'domain.example.com']
            $suffix = $hostParts[$i] . ($i < $startAtArrayCount ? '.' : '') . $suffix;
            $suffixes[($i - 1)] = $suffix;
        }

        // And now sort the array by the numeric keys ascending.
        ksort($suffixes);

        return $suffixes;
    }
}
