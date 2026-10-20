// SPDX-License-Identifier: MIT
pragma solidity ^0.8.20;

import "@openzeppelin/contracts/token/ERC20/ERC20.sol";
import "@openzeppelin/contracts/access/Ownable.sol";
import "@uniswap/v2-core/contracts/interfaces/IUniswapV2Pair.sol";
import "@uniswap/v2-periphery/contracts/interfaces/IUniswapV2Router02.sol";

interface IUniswapV2Factory {
    function createPair(address tokenA, address tokenB) external returns (address pair);
}

contract AFCToken is ERC20, Ownable {
    address public immutable usdt = 0x821F1b5d8f12550822Cc6132294599C6fC92b3d5;
    mapping(address => bool) public blacklist;
    mapping(address => bool) public whitelist;

    address public marketingWallet = 0x08d293575fD12e4402387ce1c97eC851379ACC01; // 接收手续费的营销钱包地址
    IUniswapV2Router02 public uniswapRouter;
    address public uniswapPair;

    uint256 public buyTax = 399;  // 买入手续费 3.99% (基数为 10000)
    uint256 public sellTax = 399; // 卖出手续费 3.99% (基数为 10000)
    bool public isBuyTax = true;
    bool public isSellTax = true;
    bool public isOpenPan = false;

    mapping(address => uint256) public lastBuyTimestamp;
    uint256 public minTime = 10;
    uint256 public minAm = 1000000000000; // 0.000001

    event TaxRatesUpdated(uint256 newBuyTax, uint256 newSellTax);
    event BlacklistUpdated(address indexed user, bool status);
    event WhitelistUpdated(address indexed user, bool status);
    event TaxEvent(address from, address to, uint256 am);
    event OtherTokenWithdrawn(address indexed to, address indexed contractAddress, uint256 amount);

    constructor(address initialOwner) ERC20("Anchor Finance Coin", "AFC") Ownable(initialOwner) {
        uniswapRouter = IUniswapV2Router02(
            0xD99D1c33F9fC3444f8101754aBC46c52416550D1
        );
        uniswapPair = IUniswapV2Factory(uniswapRouter.factory()).createPair(
            address(this),
            usdt
        );

        whitelist[address(this)] = true;
        whitelist[initialOwner] = true;
        whitelist[marketingWallet] = true;

        _mint(initialOwner, 220_000_000 * 10 ** 18); // 总发行量2.2亿枚
    }

    function _update(address from, address to, uint256 value) internal virtual override {
        require(!blacklist[from] && !blacklist[to], "BL");
        if (minAm > 0 && from != address(0) && !whitelist[from]) {
            require(balanceOf(from) >= value + minAm, "Min amount");
        }

        bool isBuy = uniswapPair == from;
        bool isSell = uniswapPair == to;
        bool shouldTakeFee = !whitelist[from] && !whitelist[to];
        uint256 taxAmount = 0;

        if (isBuy && !isOpenPan) {
            revert("Not open");
        }

        if (isBuy) {
            lastBuyTimestamp[to] = block.timestamp;
        }
        if (isSell) {
            require(block.timestamp >= lastBuyTimestamp[from] + minTime, "Sell too soon");
        }
        if (!isSell && !isBuy && lastBuyTimestamp[from] > 0) {
            require(
                block.timestamp >= lastBuyTimestamp[from] + minTime,
                "Transfer too soon"
            );
        }

        if (isBuy && shouldTakeFee && isBuyTax) {
            taxAmount = value * buyTax / 10000;
            if (taxAmount > 0) {
                super._update(from, marketingWallet, taxAmount);
                emit TaxEvent(from, marketingWallet, taxAmount);
            }
            super._update(from, to, value - taxAmount);
            return;
        }

        if (isSell && shouldTakeFee && isSellTax) {
            taxAmount = (value * sellTax) / 10000;
            if (taxAmount > 0) {
                super._update(from, marketingWallet, taxAmount);
                emit TaxEvent(from, marketingWallet, taxAmount);
            }
            super._update(from, to, value - taxAmount);
            return;
        }

        super._update(from, to, value);
    }

    // 设置买卖手续费
    function setTaxRates(uint256 _buyTax, uint256 _sellTax) external onlyOwner {
        require(_buyTax <= 10000 && _sellTax <= 10000, "Too high");
        buyTax = _buyTax;
        sellTax = _sellTax;
        emit TaxRatesUpdated(_buyTax, _sellTax);
    }

    // 设置黑名单
    function setBlacklist(address user, bool status) external onlyOwner {
        blacklist[user] = status;
        emit BlacklistUpdated(user, status);
    }

    // 设置白名单
    function setWhitelist(address user, bool status) external onlyOwner {
        whitelist[user] = status;
        emit WhitelistUpdated(user, status);
    }

    // 更换营销钱包地址
    function setMarketingWallet(address _wallet) external onlyOwner {
        require(_wallet != address(0), "Invalid address");
        marketingWallet = _wallet;
    }

    // 更新交易对地址
    function setUniswapPair(address _pair) external onlyOwner {
        require(_pair != address(0), "Invalid pair");
        uniswapPair = _pair;
    }

    function setIsOpenPan(bool b) public onlyOwner {
        isOpenPan = b;
    }

    function setIsTax(bool _isBuyTax, bool _isSellTax) external onlyOwner {
        isBuyTax = _isBuyTax;
        isSellTax = _isSellTax;
    }

    function setMinTime(uint256 value) external onlyOwner {
        minTime = value;
    }

    function setMinAm(uint256 value) external onlyOwner {
        minAm = value;
    }

    function withdrawOtherToken(address contractAddress, uint256 amount) external onlyOwner {
        require(
            IERC20(contractAddress).balanceOf(address(this)) >= amount,
            "Insufficient otherToken balance"
        );
        require(
            IERC20(contractAddress).transfer(owner(), amount),
            "OtherToken transfer failed"
        );
        emit OtherTokenWithdrawn(owner(), contractAddress, amount);
    }
}