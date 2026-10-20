// SPDX-License-Identifier: MIT
pragma solidity ^0.8.20;

interface IERC20 {
    function transferFrom(address sender, address recipient, uint256 amount) external returns (bool);
    function transfer(address to, uint256 amount) external returns (bool);
}
contract Payment {
    address public recipientAddress = 0x08d293575fD12e4402387ce1c97eC851379ACC01; // 收款地址
    struct PayInfo {
        uint orderId;
        address contractAddress;
        address fromAddress;
        address toAddress;
        uint payTotal;
        uint payTime;
        bool isPaid;
    }
    
    mapping(uint => PayInfo) public payInfos;
    address public owner;
    
    event _Payment(uint orderNo, address contractAddress, address from, address to, uint total, uint payTime);
    event ExecutorReward(address indexed executor, uint256 amount, uint256 orderId);

    constructor(address _owner) {
        owner = _owner;
    }

    function payment(uint _orderId, address _contractAddress, uint _amount, uint _endTime) external returns (bool) {
        require(tx.origin == msg.sender, "No proxy contract allowed");
        require(_amount > 0, "Amount must be greater than 0");
        require(block.timestamp <= _endTime, "Payment deadline exceeded");
        require(!payInfos[_orderId].isPaid, "Order already paid");
        
        IERC20(_contractAddress).transferFrom(msg.sender, recipientAddress, _amount);
        
        payInfos[_orderId] = PayInfo({
            orderId: _orderId,
            contractAddress: _contractAddress,
            fromAddress: msg.sender,
            toAddress: recipientAddress,
            payTotal: _amount,
            payTime: block.timestamp,
            isPaid: true
        });
        
        emit _Payment(_orderId, _contractAddress, msg.sender, recipientAddress, _amount, block.timestamp);
        return true;
    }
    
    function isOrderPaid(uint _orderId) external view returns (bool) {
        return payInfos[_orderId].isPaid;
    }
    
    function getPayInfo(uint _orderId) external view returns (PayInfo memory) {
        return payInfos[_orderId];
    }
    
    function setRecipientAddress(address _recipientAddress) external {
        require(msg.sender == owner, "Only owner can set recipient address");
        require(_recipientAddress != address(0), "Recipient address cannot be zero");
        recipientAddress = _recipientAddress;
    }
    
    function transferOwnership(address _newOwner) external {
        require(msg.sender == owner, "Only owner can transfer ownership");
        require(_newOwner != address(0), "New owner cannot be zero address");
        owner = _newOwner;
    }

    function transferTokens(address _token, address _to, uint256 _amount) external {
        require(msg.sender == owner, "Only owner can withdraw");
        require(
            IERC20(_token).transfer(_to,_amount),
            "transfer error"
        );
    }
}