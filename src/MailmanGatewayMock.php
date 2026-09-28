<?php
/**
 * Created by IntelliJ IDEA.
 * User: gavin
 * Date: 9/3/2018
 * Time: 8:27 PM
 */
namespace MailmanSync;

use Illuminate\Support\Facades\Storage;

class MailmanGatewayMock implements MailmanGatewayInterface
{
    /**
     * @var array<string, array<int, string>>
     */
    private static array $mockCache = [];

    public function subscribe(string $list, string $email, ?string $name = null): bool
    {
        $this->getCache($list);
        self::$mockCache[$list][] = $email;
        $this->writeCache($list);

        return true;
    }

    public function unsubscribe(string $list, string $email): bool
    {
        self::$mockCache[$list] = array_values(array_filter(
            $this->getCache($list),
            static fn (string $address): bool => $address !== $email
        ));
        $this->writeCache($list);

        return true;
    }

    public function change(string $list, string $emailFrom, string $emailTo): bool
    {
        self::$mockCache[$list] = array_map(
            static fn (string $address): string => $address === $emailFrom ? $emailTo : $address,
            $this->getCache($list)
        );
        $this->writeCache($list);

        return true;
    }

    /**
     * @return array<int, string>
     */
    public function roster(string $list): array
    {
        return $this->getCache($list);
    }

    /**
     * @return array<int, string>
     */
    private function getCache(string $list): array
    {
        if (! array_key_exists($list, self::$mockCache)) {
            $file = 'mockCache.'.$list.'.txt';
            if (Storage::disk('local')->exists($file)) {
                self::$mockCache[$list] = array_values(array_filter(
                    explode(PHP_EOL, (string) Storage::disk('local')->get($file))
                ));
            } else {
                self::$mockCache[$list] = [];
            }
        }

        return self::$mockCache[$list];
    }

    private function writeCache(string $list): void
    {
        Storage::disk('local')->put(
            'mockCache.'.$list.'.txt',
            implode(PHP_EOL, self::$mockCache[$list])
        );
    }
}
