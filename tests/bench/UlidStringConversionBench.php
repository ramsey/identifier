<?php

declare(strict_types=1);

namespace Ramsey\Bench\Identifier;

use Ramsey\Identifier\Ulid\Factory\UlidFactory;
use Ramsey\Identifier\Ulid\MaxUlid;
use Ramsey\Identifier\Ulid\NilUlid;
use Ramsey\Identifier\Ulid\Ulid;

use function array_map;

final class UlidStringConversionBench
{
    private const TINY_ULID = '00000000000000000000000001';
    private const HUGE_ULID = '7ZZZZZZZZZZZZZZZZZZZZZZZZZ';
    private const ULIDS_TO_BE_SHORTENED = [
        '0AW35CAAJ08SE9KV9X6CDQSXSA',
        '2QB6WWW1XN9T4V6EQRCGRQMAAH',
        '10S1K4X0D8966SX62495SYYC92',
        '0PZJYF7ET788KS1F9MGQB0A463',
        '7TGEQ98E70941VRTGA7V56X7QN',
        '2HS7G121199NVTEMVG5FB7VKC4',
        '0VV22QNNPQ9FB8ED5KVZPVSPKV',
        '3TME5Q2DY38NGSPBPA49ZHS72N',
        '76Q22MRGTW9ERVDB8R02TX7SNV',
        '2E5C0332R98VH84H1R2K26MBTK',
        '5YVM451PGT9049BH15ZVRAQEN7',
        '2HDE855NQV90MBZGEZZQQJTNPJ',
        '2XC2KYF49S8XWSYA7665NSZRXQ',
        '35N8YQ9GFV9FESM06EH2JJE32Q',
        '17RBHKJX7D96KT7H0T05SEKYA5',
        '78KDVJEJ2786NSHNT190GPXTMC',
        '6QKVXF7DEW8EGB79E4J8ANMZGD',
        '7EKVKEEPVX9RCAQ26E0FAPJC2Z',
        '7YJ34H3G9V841VYCRPENXAGZZN',
        '2DFZV7M03M86ASBNYFHE2EQ83S',
        '5BWQ9QHM11942S7X0EETKR90V5',
        '0ST8CGFM919P2RMD56BM2CX2BQ',
        '6446WATCX48ANB1K4FBYAB5KZQ',
        '7KVEZ5AF408MZAPED6ZS8039ZW',
        '7MHMZB4R308P7R17XNX23ZK8BZ',
        '6HH7J0DQH9924R8W3VZ86G430C',
        '3HC9R1H7S180TANZJCHCBHA4GQ',
        '0CDA97G2B38HG9SBKDRVTM43TF',
        '686EP3BK708CARVWSYTXK5D9A8',
        '3RX50JC78A8WN9Q69QGG3R8CCF',
        '3ED13GFKJB8BFRMXVHWNXN9DC1',
        '413QRKKSX39KCBEY682JAD4EFE',
        '62CF2XHGB68PCS469YJXF50VT5',
        '5K3SY5V5DT87ABRAF66NY9DW05',
        '0PNRMR6ZWF9VQ9NYVD8RBR6TG1',
        '7CQFXCFY9A9D0SJVHY8WJAM3H3',
        '1CDCYVK9FE8GJTGDW8G2M6ZAN0',
        '1XCYMSNCWT8AAVFY0BYWFATPSD',
        '6A88DVFBBK87N9CJ3G0WW65BAT',
        '2VM5BFN19X8R7TH16AHQ9T4WRM',
        '22MGTSM7FJ823B8N3MEZDVE9QZ',
        '3XQ58QQXNT9F7TW9KAH2KXQC1M',
        '5WEP5XDTTG89DTV887WTXK281J',
        '159KVD0TBD9ZRBAYDC7DHKY0Y0',
        '7RYD5KEK3H85VVNHBBK6XHJADF',
        '5GSHGQKWNH9QFRZRH2A71XJDD3',
        '1K7BC39YHV9KTBKEQXP7283H4Q',
        '013Z1VSABX8MTRSC41ESQ3C7KR',
        '5CY8K2PK6F9WEVBGAY8HJ1H166',
        '3BYSGV2BW589VRVEK5A8A1WZJ2',
        '57DQV6Q32G927V9STF9MY0BBZZ',
        '5NRQFMFY9S8MVA6G245FR0QNRD',
        '6MJ56M200H97XT3GQYD488WJC3',
        '7FTQX3FC6Y8ER9ZSRVF9X6A8ZR',
        '3093W66ZXA8FS840JB6JDE6J0G',
        '35K8029YG581MAXM7628WNAJVD',
        '3ES05F610N8AF93TC4J6NNEHE0',
        '0EDXTMR19K8CVB9W72WDAHHVX1',
        '278TB74ZJN8CBBBN64B3J3T904',
        '0CBBBNDA1398ZR8JC41YP0G3T5',
        '4FGD2XM7ES96DVV9AQ205V61EN',
        '2A6785KRVN8NRSTA7A1QJHEG77',
        '7DFYTGR6ST8PA942WT8RDBSSBW',
        '1XHZKFDSG38K0BAM1ND193RCH4',
        '40J9CVRY8J89XAJXBJK3Q5C9PV',
        '7CH3BQWNGJ8SPB4TDD2HNBTW6G',
        '5X6251103K8PQ9QYWTCRXDBQ8G',
        '43MTJCRC3S8VC94RWFAYQMZN67',
        '2NFW042ZKZ8HY9H37AZ9Q3JT8N',
        '3AT3X1RX1587MSPX0SRJ9NEM52',
        '5934Z23S998F7S88BET2DNKP3E',
        '1A17VECKXJ9PG9FFVF6A2RQABQ',
        '6PDR4M023Z9SRR54QW73HGDPFQ',
        '0DZHCB7NCH82Z80FF1F99EBMK2',
        '54DHMG5QGG8Q68VB301NM8C19J',
        '2J03WXSEB79MFAP0AHRWKC2MNT',
        '5CV14RQVMB9NCB1VY3AFXHPPJ5',
        '1PNQSNBK6C81QTG5336FP4WCDZ',
        '6PTS66Z0W89QHSVCEY0ZR20WDP',
        '6TYFYYJGEG88QT1RWCFA9TEW4H',
        '0P1X7TS8H985MRJFJEKSM69G4R',
        '0Q1H5YJ7Z690W8MXYYWDJAX6MN',
        '18CKZD11MC9F8T7YNE7ESXW87M',
        '4EMSHSRDPW8RY856CFK89B224R',
        '32DFQSABS48Z1AF4QGDT7H788Y',
        '7DWXE48PGX91698BC781ZJFPS3',
        '4PDV22P2ZQ94HSCWKT87ZE6XXW',
        '1SKNYEDCMF8X8TRM3KWCDGF7S2',
        '5B5D08DRC19W1AXRD99BZD82TG',
        '1WZGSTCWZQ97VSR0FVSY2EC16G',
        '20SW3CCX6A80BB720QVG1K8XRD',
        '2RZ7PD655B840B6APC4RHF0V41',
        '55RDF38Q858WJBPV317DEK0TGR',
        '2H6EQ3XRWB8ZXA7Q4PBHSRQSWJ',
        '2S9B6JYW809CNRQ2K0JZ5HSV1X',
        '08PFD94TSJ8FC9ZQAKXAMSDNJ9',
        '4KVKE2FASC90M90X28EVQ7NCJQ',
        '42C2GN88YC8M8ABQYCA4CZ8NZV',
        '3K59JQ35S994TS5FGTEJSJ89HP',
        '61BXAR3R278PVT6VYZXX77Q95V',
    ];
    private MaxUlid | NilUlid | Ulid $tinyUlid;
    private MaxUlid | NilUlid | Ulid $hugeUlid;
    private MaxUlid | NilUlid | Ulid $ulid;
    private UlidFactory $factory;

    /**
     * @var array<MaxUlid | NilUlid | Ulid>
     * @psalm-var non-empty-list<MaxUlid | NilUlid | Ulid>
     */
    private array $promiscuousUlids;

    private string $tinyUlidBytes;
    private string $hugeUlidBytes;
    private string $ulidBytes;

    /**
     * @var string[]
     * @psalm-var non-empty-list<string>
     */
    private array $promiscuousUlidsBytes;

    public function __construct()
    {
        $this->factory = new UlidFactory();
        $this->tinyUlid = $this->factory->createFromString(self::TINY_ULID);
        $this->hugeUlid = $this->factory->createFromString(self::HUGE_ULID);
        $this->ulid = $this->factory->createFromString(self::ULIDS_TO_BE_SHORTENED[0]);
        $this->promiscuousUlids = array_map([$this->factory, 'createFromString'], self::ULIDS_TO_BE_SHORTENED);
        $this->tinyUlidBytes = $this->tinyUlid->toBytes();
        $this->hugeUlidBytes = $this->hugeUlid->toBytes();
        $this->ulidBytes = $this->ulid->toBytes();
        $this->promiscuousUlidsBytes = array_map(
            static fn (MaxUlid | NilUlid | Ulid $ulid): string => $ulid->toBytes(),
            $this->promiscuousUlids,
        );
    }

    public function benchCreationOfTinyUlidFromString(): void
    {
        $this->factory->createFromString(self::TINY_ULID);
    }

    public function benchCreationOfHugeUlidFromString(): void
    {
        $this->factory->createFromString(self::HUGE_ULID);
    }

    public function benchCreationOfUlidFromString(): void
    {
        $this->factory->createFromString(self::ULIDS_TO_BE_SHORTENED[0]);
    }

    public function benchCreationOfPromiscuousUlidsFromString(): void
    {
        array_map([$this->factory, 'createFromString'], self::ULIDS_TO_BE_SHORTENED);
    }

    public function benchCreationOfTinyUlidFromBytes(): void
    {
        $this->factory->createFromBytes($this->tinyUlidBytes);
    }

    public function benchCreationOfHugeUlidFromBytes(): void
    {
        $this->factory->createFromBytes($this->hugeUlidBytes);
    }

    public function benchCreationOfUlidFromBytes(): void
    {
        $this->factory->createFromBytes($this->ulidBytes);
    }

    public function benchCreationOfPromiscuousUlidsFromBytes(): void
    {
        array_map([$this->factory, 'createFromBytes'], $this->promiscuousUlidsBytes);
    }

    public function benchStringConversionOfTinyUlid(): void
    {
        $this->tinyUlid->toString();
    }

    public function benchStringConversionOfHugeUlid(): void
    {
        $this->hugeUlid->toString();
    }

    public function benchStringConversionOfUlid(): void
    {
        $this->ulid->toString();
    }

    public function benchStringConversionOfPromiscuousUlids(): void
    {
        array_map(static fn (MaxUlid | NilUlid | Ulid $ulid): string => $ulid->toString(), $this->promiscuousUlids);
    }

    public function benchBytesConversionOfTinyUlid(): void
    {
        $this->tinyUlid->toBytes();
    }

    public function benchBytesConversionOfHugeUlid(): void
    {
        $this->hugeUlid->toBytes();
    }

    public function benchBytesConversionOfUlid(): void
    {
        $this->ulid->toBytes();
    }

    public function benchBytesConversionOfPromiscuousUlids(): void
    {
        array_map(static fn (MaxUlid | NilUlid | Ulid $ulid): string => $ulid->toBytes(), $this->promiscuousUlids);
    }
}
